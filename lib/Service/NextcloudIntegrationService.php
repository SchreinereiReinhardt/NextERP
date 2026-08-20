<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use OCP\Calendar\ICreateFromString;
use OCP\Calendar\IManager as CalendarManager;
use OCP\Contacts\IManager as ContactsManager;
use OCP\IAddressBook;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IUserSession;

final class NextcloudIntegrationService {
    private const CALENDAR_KEY = 'calendar_key';
    private const CALENDAR_NAME = 'calendar_name';
    private const CALENDAR_LAST_SYNC = 'calendar_last_sync';
    private const CALENDAR_LAST_ERROR = 'calendar_last_error';

    public function __construct(
        private ContactsManager $contacts,
        private CalendarManager $calendars,
        private IConfig $config,
        private IUserSession $userSession,
        private IDBConnection $db,
    ) {
    }

    public function contactsEnabled(): bool {
        return $this->userSession->getUser() !== null && ($this->nativeAddressBooks() !== [] || $this->contacts->isEnabled());
    }

    /**
     * @return array<int, array{key:string,uri:string,name:string,shared:bool,system:bool}>
     */
    public function addressBooks(): array {
        $books = $this->nativeAddressBooks();
        if ($books !== []) {
            return array_map(static fn(array $book): array => [
                'key' => (string)$book['id'],
                'uri' => (string)$book['uri'],
                'name' => (string)$book['name'],
                'shared' => false,
                'system' => false,
            ], $books);
        }

        $result = [];
        try {
            foreach ($this->contacts->getUserAddressBooks() as $book) {
                $result[] = [
                    'key' => (string)$book->getKey(),
                    'uri' => (string)$book->getUri(),
                    'name' => (string)$book->getDisplayName(),
                    'shared' => $book->isShared(),
                    'system' => $book->isSystemAddressBook(),
                ];
            }
        } catch (\Throwable) {
        }
        usort($result, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $result;
    }

    /**
     * @return array<int, array{key:string,name:string,shared:bool}>
     */
    public function writableAddressBooks(): array {
        $result = [];
        foreach ($this->nativeAddressBooks() as $book) {
            $result[] = [
                'key' => (string)$book['id'],
                'name' => (string)$book['name'],
                'shared' => false,
            ];
        }
        if ($result !== []) {
            return $result;
        }
        try {
            foreach ($this->contacts->getUserAddressBooks() as $book) {
                if (!$book->isSystemAddressBook()) {
                    $result[] = ['key'=>(string)$book->getKey(),'name'=>(string)$book->getDisplayName(),'shared'=>$book->isShared()];
                }
            }
        } catch (\Throwable) {
        }
        usort($result, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $result;
    }

    /**
     * @return array<string,mixed>
     */
    public function createCustomerContact(string $addressBookKey, string $name, ?string $contactName, ?string $phone, ?string $mobile, ?string $email, ?string $street, ?string $postalCode, ?string $city, ?string $country): array {
        $book = $this->nativeAddressBookByKey($addressBookKey);
        if ($book !== null) {
            $uid = bin2hex(random_bytes(16));
            $uri = $uid . '.vcf';
            $cardData = $this->buildVCard($uid, $name, $contactName, $phone, $mobile, $email, $street, $postalCode, $city, $country);
            $backend = $this->cardDavBackend();
            $backend->createCard((int)$book['id'], $uri, $cardData);
            return $this->nativeContactFromCard((int)$book['id'], $uri, $cardData, (string)$book['name']);
        }
        throw new \InvalidArgumentException('Bitte ein beschreibbares Nextcloud-Adressbuch auswählen.');
    }

    /**
     * @return array<string,mixed>
     */
    public function updateCustomerContact(string $addressBookKey, string $contactId, string $name, ?string $contactName, ?string $phone, ?string $mobile, ?string $email, ?string $street, ?string $postalCode, ?string $city, ?string $country, ?string $uid = null): array {
        $book = $this->nativeAddressBookByKey($addressBookKey);
        if ($book === null) {
            throw new \InvalidArgumentException('Das verbundene Nextcloud-Adressbuch ist nicht verfügbar.');
        }
        $contactUid = trim((string)$uid) !== '' ? trim((string)$uid) : preg_replace('/\.vcf$/i', '', $contactId);
        $uri = str_ends_with(strtolower($contactId), '.vcf') ? $contactId : $contactId . '.vcf';
        $cardData = $this->buildVCard($contactUid, $name, $contactName, $phone, $mobile, $email, $street, $postalCode, $city, $country);
        $backend = $this->cardDavBackend();
        try {
            $backend->updateCard((int)$book['id'], $uri, $cardData);
        } catch (\Throwable) {
            $backend->createCard((int)$book['id'], $uri, $cardData);
        }
        return $this->nativeContactFromCard((int)$book['id'], $uri, $cardData, (string)$book['name']);
    }

    private function addressBookByKey(string $addressBookKey): ?IAddressBook {
        if ($addressBookKey === '') {
            return null;
        }
        foreach ($this->contacts->getUserAddressBooks() as $book) {
            if ((string)$book->getKey() === $addressBookKey) {
                return $book;
            }
        }
        return null;
    }

    /** @return array<string,mixed> */
    private function customerContactProperties(string $name, ?string $contactName, ?string $phone, ?string $email, ?string $address, ?string $contactId, ?string $uid): array {
        $displayName = trim((string)$contactName) !== '' ? trim((string)$contactName) : trim($name);
        $properties = [
            'FN' => $displayName,
            'ORG' => trim($name),
            'UID' => trim((string)$uid) !== '' ? trim((string)$uid) : bin2hex(random_bytes(16)),
        ];
        if ($contactId !== null && $contactId !== '') {
            $properties['id'] = $contactId;
        }
        if (trim((string)$email) !== '') {
            $properties['EMAIL'] = trim((string)$email);
        }
        if (trim((string)$phone) !== '') {
            $properties['TEL'] = trim((string)$phone);
        }
        if (trim((string)$address) !== '') {
            $properties['ADR'] = $this->addressToVCard((string)$address);
        }
        return $properties;
    }

    private function addressToVCard(string $address): string {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R+/', $address) ?: []), static fn(string $line): bool => $line !== ''));
        $street = $lines[0] ?? '';
        $postal = '';
        $city = '';
        $country = '';
        if (isset($lines[1]) && preg_match('/^([0-9A-Za-z-]+)\s+(.+)$/u', $lines[1], $matches)) {
            $postal = $matches[1];
            $city = $matches[2];
        } elseif (isset($lines[1])) {
            $city = $lines[1];
        }
        if (isset($lines[2])) {
            $country = $lines[2];
        }
        return ';;' . str_replace(';', ',', $street) . ';' . str_replace(';', ',', $city) . ';;' . str_replace(';', ',', $postal) . ';' . str_replace(';', ',', $country);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function contactsForSelection(int $limitPerBook = 500): array {
        $books = $this->nativeAddressBooks();
        if ($books !== []) {
            $result = [];
            foreach ($books as $book) {
                $qb = $this->db->getQueryBuilder();
                $qb->select('uri', 'carddata')->from('cards')
                    ->where($qb->expr()->eq('addressbookid', $qb->createNamedParameter((int)$book['id'])))
                    ->orderBy('id', 'ASC')->setMaxResults($limitPerBook);
                foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
                    try {
                        $result[] = $this->nativeContactFromCard((int)$book['id'], (string)$row['uri'], (string)$row['carddata'], (string)$book['name']);
                    } catch (\Throwable) {
                    }
                }
            }
            usort($result, static fn(array $a, array $b): int => strcasecmp((string)$a['label'], (string)$b['label']));
            return $result;
        }
        return [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findContact(string $addressBookKey, string $contactId): ?array {
        $book = $this->nativeAddressBookByKey($addressBookKey);
        if ($book === null) {
            return null;
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select('uri', 'carddata')->from('cards')
            ->where($qb->expr()->eq('addressbookid', $qb->createNamedParameter((int)$book['id'])))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->eq('uri', $qb->createNamedParameter($contactId)),
                $qb->expr()->eq('uri', $qb->createNamedParameter(str_ends_with(strtolower($contactId), '.vcf') ? $contactId : $contactId . '.vcf'))
            ))->setMaxResults(1);
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ? $this->nativeContactFromCard((int)$book['id'], (string)$row['uri'], (string)$row['carddata'], (string)$book['name']) : null;
    }

    /**
     * @return array<int, array{key:string,name:string,writable:bool,selected:bool}>
     */
    public function availableCalendars(): array {
        $selected = $this->selectedCalendarKey();
        $result = [];
        foreach ($this->nativeCalendars() as $calendar) {
            $key = (string)$calendar['uri'];
            $result[] = [
                'key' => $key,
                'name' => (string)$calendar['name'],
                'writable' => true,
                'selected' => $selected !== '' && hash_equals($selected, $key),
            ];
        }
        usort($result, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        return $result;
    }

    public function selectedCalendarKey(): string {
        return $this->config->getAppValue('reinhardterp', self::CALENDAR_KEY, '');
    }

    public function selectedCalendarName(): string {
        return $this->config->getAppValue('reinhardterp', self::CALENDAR_NAME, '');
    }


    public function lastCalendarSync(): string {
        return $this->config->getAppValue('reinhardterp', self::CALENDAR_LAST_SYNC, '');
    }

    public function lastCalendarError(): string {
        return $this->config->getAppValue('reinhardterp', self::CALENDAR_LAST_ERROR, '');
    }

    /**
     * Holt Termine aus dem ausgewählten Nextcloud-Kalender und gleicht sie mit
     * der lokalen Teamtermin-Tabelle ab. Nextcloud bleibt die führende Quelle.
     *
     * @return array{imported:int,updated:int,removed:int,total:int,from:string,to:string}
     */
    public function syncCalendarEvents(?DateTimeImmutable $from = null, ?DateTimeImmutable $to = null): array {
        $selectedKey = $this->selectedCalendarKey();
        if ($selectedKey === '') {
            return ['imported'=>0,'updated'=>0,'removed'=>0,'total'=>0,'from'=>'','to'=>''];
        }
        $calendar = $this->nativeCalendarByUri($selectedKey);
        if ($calendar === null) {
            $repair = $this->repairCalendarSelection();
            $selectedKey = (string)($repair['selectedKey'] ?? '');
            $calendar = $selectedKey !== '' ? $this->nativeCalendarByUri($selectedKey) : null;
            if ($calendar === null) {
                $message = 'Der konfigurierte Nextcloud-Kalender ist nicht verfügbar und es wurde kein Ersatzkalender gefunden.';
                $this->config->setAppValue('reinhardterp', self::CALENDAR_LAST_ERROR, $message);
                throw new \RuntimeException($message);
            }
        }
        $from ??= (new DateTimeImmutable('today'))->modify('-90 days');
        $to ??= (new DateTimeImmutable('today'))->modify('+400 days')->setTime(23,59,59);
        $qb = $this->db->getQueryBuilder();
        $qb->select('uri','calendardata','uid','firstoccurence','lastoccurence')->from('calendarobjects')
            ->where($qb->expr()->eq('calendarid',$qb->createNamedParameter((int)$calendar['id'])))
            ->andWhere($qb->expr()->eq('componenttype',$qb->createNamedParameter('VEVENT')))
            ->andWhere($qb->expr()->lte('firstoccurence',$qb->createNamedParameter($to->getTimestamp())))
            ->andWhere($qb->expr()->gte('lastoccurence',$qb->createNamedParameter($from->getTimestamp())))
            ->orderBy('firstoccurence','ASC');
        $rows=$qb->executeQuery()->fetchAllAssociative();
        $found=[];$imported=0;$updated=0;
        foreach($rows as $row){
            try{$event=$this->nativeEventFromIcs((string)$row['uri'],(string)$row['calendardata'],$selectedKey);}catch(\Throwable){$event=null;}
            if($event===null)continue;
            $found[$event['calendar_object_uri']]=true;
            $existing=$this->findTeamEvent($selectedKey,$event['calendar_object_uri']);
            $data=[
                'title'=>$event['title'],'start_at'=>$event['start_at'],'end_at'=>$event['end_at'],
                'location'=>$event['location'],'description'=>$event['description'],'calendar_uri'=>$selectedKey,
                'calendar_object_uri'=>$event['calendar_object_uri'],'calendar_uid'=>$event['calendar_uid'],
                'sync_source'=>$existing!==null?(string)($existing['sync_source']??'nextcloud'):'nextcloud',
                'sync_hash'=>$event['sync_hash'],'is_deleted'=>0,'last_synced_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
            if($existing===null){$data['created_by']='nextcloud';$data['created_at']=date('Y-m-d H:i:s');$this->insertTeamEvent($data);$imported++;}
            elseif((string)($existing['sync_hash']??'')!==$event['sync_hash']||!empty($existing['is_deleted'])){$this->updateTeamEvent((int)$existing['id'],$data);$updated++;}
            else{$this->updateTeamEvent((int)$existing['id'],['is_deleted'=>0,'last_synced_at'=>date('Y-m-d H:i:s')]);}
        }
        $removed=0;
        foreach($this->calendarTeamEvents($selectedKey,$from,$to) as $local){$uri=(string)($local['calendar_object_uri']??'');if($uri===''||isset($found[$uri])||!empty($local['is_deleted']))continue;$this->updateTeamEvent((int)$local['id'],['is_deleted'=>1,'last_synced_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$removed++;}
        $now=date('Y-m-d H:i:s');$this->config->setAppValue('reinhardterp',self::CALENDAR_LAST_SYNC,$now);$this->config->deleteAppValue('reinhardterp',self::CALENDAR_LAST_ERROR);
        return ['imported'=>$imported,'updated'=>$updated,'removed'=>$removed,'total'=>count($found),'from'=>$from->format('Y-m-d H:i:s'),'to'=>$to->format('Y-m-d H:i:s')];
    }


    /**
     * Prüft die gespeicherte Kalenderauswahl und repariert sie automatisch.
     *
     * @return array{changed:bool,selectedKey:string,selectedName:string,message:string}
     */
    public function repairCalendarSelection(): array {
        $current = $this->selectedCalendarKey();
        $calendars = $this->availableCalendars();

        foreach ($calendars as $calendar) {
            if ($current !== '' && hash_equals($current, (string)$calendar['key'])) {
                $this->config->setAppValue('reinhardterp', self::CALENDAR_NAME, (string)$calendar['name']);
                return [
                    'changed' => false,
                    'selectedKey' => (string)$calendar['key'],
                    'selectedName' => (string)$calendar['name'],
                    'message' => 'Die Kalenderverbindung ist gültig.',
                ];
            }
        }

        foreach ($calendars as $calendar) {
            if (!empty($calendar['writable'])) {
                $this->config->setAppValue('reinhardterp', self::CALENDAR_KEY, (string)$calendar['key']);
                $this->config->setAppValue('reinhardterp', self::CALENDAR_NAME, (string)$calendar['name']);
                $this->config->deleteAppValue('reinhardterp', self::CALENDAR_LAST_ERROR);
                return [
                    'changed' => true,
                    'selectedKey' => (string)$calendar['key'],
                    'selectedName' => (string)$calendar['name'],
                    'message' => 'Der Kalender wurde automatisch auf „' . (string)$calendar['name'] . '“ gesetzt.',
                ];
            }
        }

        $this->config->deleteAppValue('reinhardterp', self::CALENDAR_KEY);
        $this->config->deleteAppValue('reinhardterp', self::CALENDAR_NAME);
        return [
            'changed' => $current !== '',
            'selectedKey' => '',
            'selectedName' => '',
            'message' => 'Es wurde kein beschreibbarer Nextcloud-Kalender gefunden.',
        ];
    }

    /**
     * Führt eine vollständige Integrationsprüfung mit Selbstheilung durch.
     *
     * @return array<string,mixed>
     */
    public function repairIntegration(): array {
        $calendar = $this->repairCalendarSelection();
        $status = $this->integrationStatus();
        $status['calendarRepair'] = $calendar;
        $status['healthy'] = ($status['addressBookCount'] ?? 0) > 0
            && ($status['calendarCount'] ?? 0) > 0
            && ($calendar['selectedKey'] ?? '') !== '';
        return $status;
    }

    public function saveCalendarSelection(string $calendarKey): void {
        $calendarKey = trim($calendarKey);
        if ($calendarKey === '') {
            $this->config->deleteAppValue('reinhardterp', self::CALENDAR_KEY);
            $this->config->deleteAppValue('reinhardterp', self::CALENDAR_NAME);
            return;
        }

        foreach ($this->availableCalendars() as $calendar) {
            if ($calendar['key'] !== $calendarKey) {
                continue;
            }
            if (!$calendar['writable']) {
                throw new \InvalidArgumentException('Der ausgewählte Nextcloud-Kalender ist schreibgeschützt.');
            }
            $this->config->setAppValue('reinhardterp', self::CALENDAR_KEY, $calendarKey);
            $this->config->setAppValue('reinhardterp', self::CALENDAR_NAME, $calendar['name']);
            return;
        }

        throw new \InvalidArgumentException('Der ausgewählte Nextcloud-Kalender wurde nicht gefunden.');
    }

    /**
     * @return array{calendarKey:string,calendarName:string,objectUri:string}|null
     */
    public function createCalendarEvent(string $title,string $startAt,?string $endAt,?string $location,?string $description): ?array {
        $selectedKey=$this->selectedCalendarKey();if($selectedKey==='')return null;
        $calendar=$this->nativeCalendarByUri($selectedKey);if($calendar===null)throw new \RuntimeException('Der konfigurierte Nextcloud-Kalender ist nicht verfügbar oder nicht beschreibbar.');
        $start=new DateTimeImmutable($startAt);$end=$endAt!==null&&trim($endAt)!==''?new DateTimeImmutable($endAt):$start->modify('+1 hour');if($end<=$start)throw new \InvalidArgumentException('Das Terminende muss nach dem Beginn liegen.');
        $uid=bin2hex(random_bytes(16)).'@nexterp';$uri=$uid.'.ics';$ics=$this->buildIcs($uid,trim($title),$start,$end,$location,$description);
        $backend=$this->calDavBackend();$backend->createCalendarObject((int)$calendar['id'],$uri,$ics);
        return ['calendarKey'=>$selectedKey,'calendarName'=>$this->selectedCalendarName(),'objectUri'=>$uri];
    }

    private function selectedCalendar(): ?object {
        $uid = $this->userSession->getUser()?->getUID();
        $selectedKey = $this->selectedCalendarKey();
        if ($uid === null || $selectedKey === '') {
            return null;
        }
        try {
            foreach ($this->calendars->getCalendarsForPrincipal('principals/users/' . $uid) as $calendar) {
                if ($this->calendarUri($calendar) === $selectedKey) {
                    return $calendar;
                }
            }
        } catch (\Throwable) {
            return null;
        }
        return null;
    }

    /** @param array<string,mixed> $raw */
    private function normaliseCalendarEvent(array $raw, string $calendarKey): ?array {
        $objects = $raw['objects'] ?? [];
        if (!is_array($objects) || $objects === []) {
            return null;
        }
        $object = null;
        foreach ($objects as $candidate) {
            if (is_array($candidate)) {
                $object = $candidate;
                break;
            }
        }
        if ($object === null) {
            return null;
        }

        $start = $this->calendarDate($object['DTSTART'] ?? null);
        if ($start === null) {
            return null;
        }
        $end = $this->calendarDate($object['DTEND'] ?? null);
        if ($end === null && isset($object['DURATION'])) {
            $duration = $this->calendarScalar($object['DURATION']);
            try {
                $end = $duration !== '' ? $start->add(new DateInterval($duration)) : null;
            } catch (\Throwable) {
                $end = null;
            }
        }
        $end ??= $start->modify('+1 hour');
        if ($end <= $start) {
            $end = $start->modify('+1 hour');
        }

        $uri = trim((string)($raw['uri'] ?? $raw['id'] ?? ''));
        $calendarUid = $this->calendarScalar($object['UID'] ?? null);
        if ($uri === '') {
            $uri = $calendarUid !== '' ? $calendarUid . '.ics' : hash('sha256', json_encode($raw));
        }
        $title = $this->calendarScalar($object['SUMMARY'] ?? null);
        if ($title === '') {
            $title = 'Termin';
        }
        $data = [
            'title' => $title,
            'start_at' => $start->format('Y-m-d H:i:s'),
            'end_at' => $end->format('Y-m-d H:i:s'),
            'location' => $this->calendarScalar($object['LOCATION'] ?? null) ?: null,
            'description' => $this->calendarScalar($object['DESCRIPTION'] ?? null) ?: null,
            'calendar_uri' => $calendarKey,
            'calendar_object_uri' => $uri,
            'calendar_uid' => $calendarUid !== '' ? $calendarUid : null,
        ];
        $data['sync_hash'] = hash('sha256', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $data;
    }

    private function calendarScalar(mixed $property): string {
        if ($property instanceof DateTimeInterface) {
            return $property->format(DateTimeInterface::ATOM);
        }
        if (is_string($property) || is_numeric($property)) {
            return trim((string)$property);
        }
        if (!is_array($property)) {
            return '';
        }
        if (array_key_exists(0, $property) && !is_array($property[0])) {
            return trim((string)$property[0]);
        }
        foreach ($property as $value) {
            $scalar = $this->calendarScalar($value);
            if ($scalar !== '') {
                return $scalar;
            }
        }
        return '';
    }

    private function calendarDate(mixed $property): ?DateTimeImmutable {
        if ($property instanceof DateTimeImmutable) {
            return $property;
        }
        if ($property instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($property);
        }
        $params = [];
        $value = $property;
        if (is_array($property) && array_key_exists(0, $property)) {
            $value = $property[0];
            $params = is_array($property[1] ?? null) ? $property[1] : [];
        }
        if (!is_string($value) && !is_numeric($value)) {
            return null;
        }
        $value = trim((string)$value);
        $timezone = null;
        if (!empty($params['TZID'])) {
            try {
                $timezone = new DateTimeZone((string)$params['TZID']);
            } catch (\Throwable) {
                $timezone = null;
            }
        }
        foreach (['!Ymd\THis\Z' => new DateTimeZone('UTC'), '!Ymd\THis' => $timezone, '!Ymd' => $timezone] as $format => $tz) {
            $date = DateTimeImmutable::createFromFormat($format, $value, $tz ?: null);
            if ($date instanceof DateTimeImmutable) {
                return $date;
            }
        }
        try {
            return new DateTimeImmutable($value, $timezone ?: null);
        } catch (\Throwable) {
            return null;
        }
    }

    private function findTeamEvent(string $calendarKey, string $objectUri): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_team_events')
            ->where($qb->expr()->eq('calendar_uri', $qb->createNamedParameter($calendarKey)))
            ->andWhere($qb->expr()->eq('calendar_object_uri', $qb->createNamedParameter($objectUri)))
            ->setMaxResults(1);
        $row = $qb->executeQuery()->fetchAssociative();
        return $row ?: null;
    }

    private function calendarTeamEvents(string $calendarKey, DateTimeImmutable $from, DateTimeImmutable $to): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_team_events')
            ->where($qb->expr()->eq('calendar_uri', $qb->createNamedParameter($calendarKey)))
            ->andWhere($qb->expr()->gte('start_at', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('start_at', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))));
        return $qb->executeQuery()->fetchAllAssociative();
    }

    private function insertTeamEvent(array $data): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_team_events');
        foreach ($data as $column => $value) {
            $qb->setValue($column, $qb->createNamedParameter($value));
        }
        $qb->executeStatement();
    }

    private function updateTeamEvent(int $id, array $data): void {
        $qb = $this->db->getQueryBuilder();
        $qb->update('re_erp_team_events');
        foreach ($data as $column => $value) {
            $qb->set($column, $qb->createNamedParameter($value));
        }
        $qb->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeStatement();
    }

    /** @return array<string,mixed> */
    public function integrationStatus(): array {
        $books=$this->addressBooks();$contacts=$this->contactsForSelection(5000);$calendars=$this->availableCalendars();
        return ['contactsEnabled'=>$this->contactsEnabled(),'addressBookCount'=>count($books),'contactCount'=>count($contacts),'addressBooks'=>$books,'calendarCount'=>count($calendars),'calendars'=>$calendars,'selectedCalendarKey'=>$this->selectedCalendarKey(),'selectedCalendarName'=>$this->selectedCalendarName(),'lastCalendarSync'=>$this->lastCalendarSync(),'lastCalendarError'=>$this->lastCalendarError(),'provider'=>'Native DAV'];
    }

    /** @return array<int,array{id:int,uri:string,name:string}> */
    private function nativeAddressBooks(): array {
        $uid=$this->userSession->getUser()?->getUID();if($uid===null)return [];
        $principal='principals/users/'.$uid;$qb=$this->db->getQueryBuilder();
        $qb->select('id','uri','displayname')->from('addressbooks')->where($qb->expr()->eq('principaluri',$qb->createNamedParameter($principal)))->orderBy('displayname','ASC');
        $result=[];foreach($qb->executeQuery()->fetchAllAssociative() as $row){$result[]=['id'=>(int)$row['id'],'uri'=>(string)$row['uri'],'name'=>(string)($row['displayname']?:$row['uri'])];}return $result;
    }

    private function nativeAddressBookByKey(string $key): ?array {
        foreach($this->nativeAddressBooks() as $book){if((string)$book['id']===$key||(string)$book['uri']===$key)return $book;}return null;
    }

    /** @return array<int,array{id:int,uri:string,name:string}> */
    private function nativeCalendars(): array {
        $uid=$this->userSession->getUser()?->getUID();if($uid===null)return [];$principal='principals/users/'.$uid;
        $qb=$this->db->getQueryBuilder();$qb->select('id','uri','displayname')->from('calendars')->where($qb->expr()->eq('principaluri',$qb->createNamedParameter($principal)))->andWhere($qb->expr()->orX($qb->expr()->isNull('deleted_at'),$qb->expr()->eq('deleted_at',$qb->createNamedParameter(0))))->orderBy('displayname','ASC');
        $result=[];foreach($qb->executeQuery()->fetchAllAssociative() as $row){$result[]=['id'=>(int)$row['id'],'uri'=>(string)$row['uri'],'name'=>(string)($row['displayname']?:$row['uri'])];}return $result;
    }

    private function nativeCalendarByUri(string $uri): ?array {foreach($this->nativeCalendars() as $calendar){if((string)$calendar['uri']===$uri||(string)$calendar['id']===$uri)return $calendar;}return null;}

    private function cardDavBackend(): object {
        $class='OCA\\DAV\\CardDAV\\CardDavBackend';if(!class_exists($class))throw new \RuntimeException('Nextcloud CardDAV ist nicht verfügbar.');return \OCP\Server::get($class);
    }

    private function calDavBackend(): object {
        $class='OCA\\DAV\\CalDAV\\CalDavBackend';if(!class_exists($class))throw new \RuntimeException('Nextcloud CalDAV ist nicht verfügbar.');return \OCP\Server::get($class);
    }

    private function buildVCard(string $uid, string $name, ?string $contactName, ?string $phone, ?string $mobile, ?string $email, ?string $street, ?string $postalCode, ?string $city, ?string $country): string {
        $fn = trim((string)$contactName) !== '' ? trim((string)$contactName) : trim($name);
        $v = "BEGIN:VCARD
VERSION:3.0
UID:" . $this->vEscape($uid) . "
FN:" . $this->vEscape($fn) . "
ORG:" . $this->vEscape(trim($name)) . "
";
        if (trim((string)$email) !== '') $v .= 'EMAIL;TYPE=WORK:' . $this->vEscape(trim((string)$email)) . "
";
        if (trim((string)$phone) !== '') $v .= 'TEL;TYPE=WORK,VOICE:' . $this->vEscape(trim((string)$phone)) . "
";
        if (trim((string)$mobile) !== '') $v .= 'TEL;TYPE=CELL,VOICE:' . $this->vEscape(trim((string)$mobile)) . "
";
        if (trim((string)$street) !== '' || trim((string)$postalCode) !== '' || trim((string)$city) !== '' || trim((string)$country) !== '') {
            // ADR components: PO box;extended;street;city;region;postal code;country
            $parts = ['', '', (string)$street, (string)$city, '', (string)$postalCode, (string)$country];
            $escaped = array_map(fn(string $part): string => $this->vEscape(trim($part)), $parts);
            $v .= 'ADR;TYPE=WORK:' . implode(';', $escaped) . "
";
        }
        return $v . "END:VCARD
";
    }

    private function vEscape(string $value): string {return str_replace(["\\",";",",","\r","\n"],["\\\\","\\;","\\,",'',"\\n"],$value);}

    private function nativeContactFromCard(int $bookId,string $uri,string $cardData,string $bookName): array {
        $card=\Sabre\VObject\Reader::read($cardData);$scalar=static function($prop):string{return $prop!==null?trim((string)$prop):'';};
        $full=$scalar($card->FN??null);$org=$scalar($card->ORG??null);$label=$org!==''?$org:($full!==''?$full:$uri);$street='';$postalCode='';$city='';$country='';$adr='';if(isset($card->ADR)){$parts=$card->ADR->getParts();$street=trim((string)($parts[2]??''));$city=trim((string)($parts[3]??''));$postalCode=trim((string)($parts[5]??''));$country=trim((string)($parts[6]??''));$adr=implode("\n",array_filter([$street,trim($postalCode.' '.$city),$country]));}
        [$phone, $mobile] = $this->phoneNumbersFromVCard($card);
        return ['id'=>$uri,'uid'=>$scalar($card->UID??null),'addressBookKey'=>(string)$bookId,'addressBookName'=>$bookName,'fullName'=>$full,'organisation'=>$org,'label'=>$label,'email'=>$scalar($card->EMAIL??null),'phone'=>$phone,'mobile'=>$mobile,'street'=>$street,'postalCode'=>$postalCode,'city'=>$city,'country'=>$country,'address'=>$adr];
    }

    /** @return array{0:string,1:string} */
    private function phoneNumbersFromVCard(object $card): array {
        $phone = '';
        $mobile = '';
        if (!isset($card->TEL)) {
            return [$phone, $mobile];
        }
        foreach ($card->select('TEL') as $tel) {
            $value = trim((string)$tel);
            if ($value === '') {
                continue;
            }
            $types = strtoupper((string)($tel['TYPE'] ?? ''));
            if (str_contains($types, 'CELL') || str_contains($types, 'MOBILE')) {
                if ($mobile === '') {
                    $mobile = $value;
                }
            } elseif ($phone === '') {
                $phone = $value;
            }
        }
        if ($phone === '' && $mobile !== '') {
            return ['', $mobile];
        }
        return [$phone, $mobile];
    }

    private function buildIcs(string $uid,string $title,DateTimeImmutable $start,DateTimeImmutable $end,?string $location,?string $description): string {
        $esc=fn(string $v):string=>str_replace(["\\",",",";","\r","\n"],["\\\\","\\,","\\;",'',"\\n"],$v);$now=(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format('Ymd\\THis\\Z');
        $ics="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Betrio//DE\r\nBEGIN:VEVENT\r\nUID:".$esc($uid)."\r\nDTSTAMP:$now\r\nDTSTART:".$start->setTimezone(new DateTimeZone('UTC'))->format('Ymd\\THis\\Z')."\r\nDTEND:".$end->setTimezone(new DateTimeZone('UTC'))->format('Ymd\\THis\\Z')."\r\nSUMMARY:".$esc($title)."\r\n";
        if(trim((string)$location)!=='')$ics.='LOCATION:'.$esc(trim((string)$location))."\r\n";if(trim((string)$description)!=='')$ics.='DESCRIPTION:'.$esc(trim((string)$description))."\r\n";return $ics."END:VEVENT\r\nEND:VCALENDAR\r\n";
    }

    private function nativeEventFromIcs(string $uri,string $ics,string $calendarKey): ?array {
        $vcal=\Sabre\VObject\Reader::read($ics);$event=$vcal->VEVENT??null;if($event===null)return null;$start=$event->DTSTART->getDateTime();$end=isset($event->DTEND)?$event->DTEND->getDateTime():$start->modify('+1 hour');if($end<=$start)$end=$start->modify('+1 hour');
        $data=['title'=>trim((string)($event->SUMMARY??'Termin'))?:'Termin','start_at'=>$start->format('Y-m-d H:i:s'),'end_at'=>$end->format('Y-m-d H:i:s'),'location'=>isset($event->LOCATION)?trim((string)$event->LOCATION):null,'description'=>isset($event->DESCRIPTION)?trim((string)$event->DESCRIPTION):null,'calendar_uri'=>$calendarKey,'calendar_object_uri'=>$uri,'calendar_uid'=>isset($event->UID)?trim((string)$event->UID):null];$data['sync_hash']=hash('sha256',json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));return $data;
    }

    private function calendarUri(object $calendar): string {
        if (method_exists($calendar, 'getUri')) {
            $uri = trim((string)$calendar->getUri());
            if ($uri !== '') {
                return $uri;
            }
        }
        if (method_exists($calendar, 'getKey')) {
            return trim((string)$calendar->getKey());
        }
        return '';
    }

    /** @param array<string,mixed> $row */
    private function contactAddressBookKey(array $row): string {
        foreach (['addressbook-key', 'addressBookKey', 'addressbookKey', 'addressbook_key'] as $key) {
            if (isset($row[$key]) && trim((string)$row[$key]) !== '') {
                return trim((string)$row[$key]);
            }
        }
        return '';
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normaliseContact(IAddressBook $book, array $row): array {
        $fullName = $this->firstScalar($row['FN'] ?? null);
        $organisation = $this->firstScalar($row['ORG'] ?? null);
        $label = $organisation !== '' ? $organisation : $fullName;
        if ($label === '') {
            $label = 'Kontakt ' . (string)($row['id'] ?? '');
        }

        [$phone, $mobile] = $this->phoneNumbersFromContactRow($row['TEL'] ?? null);
        $addressParts = $this->addressPartsFromContactRow($row['ADR'] ?? null);
        return [
            'id' => (string)($row['id'] ?? ''),
            'uid' => $this->firstScalar($row['UID'] ?? null),
            'addressBookKey' => (string)$book->getKey(),
            'addressBookName' => (string)$book->getDisplayName(),
            'fullName' => $fullName,
            'organisation' => $organisation,
            'label' => $label,
            'email' => $this->firstScalar($row['EMAIL'] ?? null),
            'phone' => $phone,
            'mobile' => $mobile,
            'street' => $addressParts['street'],
            'postalCode' => $addressParts['postalCode'],
            'city' => $addressParts['city'],
            'country' => $addressParts['country'],
            'address' => implode("\n", array_filter([$addressParts['street'], trim($addressParts['postalCode'] . ' ' . $addressParts['city']), $addressParts['country']])),
        ];
    }

    /** @return array{street:string,postalCode:string,city:string,country:string} */
    private function addressPartsFromContactRow(mixed $value): array {
        $empty = ['street' => '', 'postalCode' => '', 'city' => '', 'country' => ''];
        $item = is_array($value) && array_is_list($value) ? ($value[0] ?? null) : $value;
        if ($item === null) return $empty;
        if (is_array($item) && isset($item['value'])) $item = $item['value'];
        if (is_array($item)) {
            $parts = array_values($item);
        } else {
            $parts = str_getcsv((string)$item, ';', '"', '\\');
        }
        return [
            'street' => trim((string)($parts[2] ?? '')),
            'city' => trim((string)($parts[3] ?? '')),
            'postalCode' => trim((string)($parts[5] ?? '')),
            'country' => trim((string)($parts[6] ?? '')),
        ];
    }

    /** @return array{0:string,1:string} */
    private function phoneNumbersFromContactRow(mixed $value): array {
        $phone = '';
        $mobile = '';
        $items = is_array($value) ? $value : [$value];
        foreach ($items as $item) {
            $types = '';
            $candidate = '';
            if (is_array($item)) {
                $candidate = $this->firstScalar($item['value'] ?? $item);
                $typeValue = $item['type'] ?? $item['TYPE'] ?? $item['parameters']['TYPE'] ?? '';
                $types = strtoupper(is_array($typeValue) ? implode(',', $typeValue) : (string)$typeValue);
            } else {
                $candidate = $this->firstScalar($item);
            }
            if ($candidate === '') {
                continue;
            }
            if (str_contains($types, 'CELL') || str_contains($types, 'MOBILE')) {
                if ($mobile === '') {
                    $mobile = $candidate;
                }
            } elseif ($phone === '') {
                $phone = $candidate;
            }
        }
        return [$phone, $mobile];
    }

    private function firstScalar(mixed $value): string {
        if (is_string($value) || is_numeric($value)) {
            return trim((string)$value);
        }
        if (!is_array($value)) {
            return '';
        }
        foreach ($value as $item) {
            if (is_array($item) && array_key_exists('value', $item)) {
                $item = $item['value'];
            }
            $candidate = $this->firstScalar($item);
            if ($candidate !== '') {
                return $candidate;
            }
        }
        return '';
    }

    private function formatAddress(mixed $value): string {
        $raw = $this->firstScalar($value);
        if ($raw === '') {
            return '';
        }
        if (!str_contains($raw, ';')) {
            return $raw;
        }

        $parts = array_pad(explode(';', $raw), 7, '');
        $street = trim($parts[2]);
        $city = trim($parts[5] . ' ' . $parts[3]);
        $country = trim($parts[6]);
        return implode("\n", array_values(array_filter([$street, $city, $country], static fn(string $part): bool => $part !== '')));
    }
}
