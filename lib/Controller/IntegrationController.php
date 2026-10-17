<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Db\Customer;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Service\ActivityService;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\EditionService;
use OCA\ReinhardtERP\Service\NextcloudIntegrationService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

final class IntegrationController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private CustomerMapper $customers,
        private NextcloudIntegrationService $integration,
        private PermissionService $permissions,
        private ActivityService $activities,
        private IURLGenerator $url,
        private NumberService $numbers,
        private FolderService $folders,
        private IUserSession $users,
        private EditionService $edition,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function index(): TemplateResponse {
        $this->permissions->assert('settings');
        return new TemplateResponse($this->appName, 'integration', [
            'status' => $this->integration->integrationStatus(),
        ]);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function customerImport(): TemplateResponse {
        $this->permissions->assert('customers');
        $contacts = $this->integration->contactsForSelection(1500);
        $linked = [];
        foreach ($this->customers->findAllActive() as $customer) {
            if ($customer->getNcAddressbookKey() && $customer->getNcContactId()) {
                $linked[(string)$customer->getNcAddressbookKey() . '::' . (string)$customer->getNcContactId()] = $customer->getCustomerNo() . ' · ' . $customer->getName();
            }
        }
        return new TemplateResponse($this->appName, 'customer_import', [
            'contacts' => $contacts,
            'linkedContacts' => $linked,
            'contactsEnabled' => $this->integration->contactsEnabled(),
            'addressBooks' => $this->integration->writableAddressBooks(),
            'csvProLocked' => !$this->edition->isPro(),
            'betrioProUrl' => (string)($this->edition->status()['proUrl'] ?? 'https://kassel-net.de/betrio/'),
        ]);
    }

    #[NoAdminRequired]
    public function importCustomers(array $contactSelections = []): RedirectResponse {
        $this->permissions->assert('customers');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        foreach (array_unique($contactSelections) as $selection) {
            if (!is_string($selection) || !str_contains($selection, '::')) {
                $failed++;
                continue;
            }
            [$addressBookKey, $contactId] = explode('::', $selection, 2);
            if ($this->customers->findByNextcloudContact($addressBookKey, $contactId) !== null) {
                $skipped++;
                continue;
            }
            $contact = $this->integration->findContact($addressBookKey, $contactId);
            if ($contact === null) {
                $failed++;
                continue;
            }

            try {
                $name = trim((string)($contact['organisation'] ?: $contact['fullName'] ?: $contact['label']));
                if ($name === '') {
                    $failed++;
                    continue;
                }
                $number = $this->numbers->next('customer');
                $now = new \DateTime();
                $customer = new Customer();
                $customer->setCustomerNo($number);
                $customer->setName($name);
                $customer->setContactName($contact['organisation'] !== '' && $contact['fullName'] !== '' ? $contact['fullName'] : null);
                $customer->setPhone($this->nullable((string)$contact['phone']));
                $customer->setMobile($this->nullable((string)($contact['mobile'] ?? '')));
                $customer->setEmail($this->nullable((string)$contact['email']));
                $customer->setStreet($this->nullable((string)($contact['street'] ?? '')));
                $customer->setPostalCode($this->nullable((string)($contact['postalCode'] ?? '')));
                $customer->setCity($this->nullable((string)($contact['city'] ?? '')));
                $customer->setCountry($this->nullable((string)($contact['country'] ?? '')));
                $customer->setAddress($this->nullable((string)$contact['address']));
                $customer->setFolderPath($this->folders->ensureCustomerFolder($number, $name));
                $customer->setNcAddressbookKey($addressBookKey);
                $customer->setNcContactId($contactId);
                $customer->setNcContactUid(($contact['uid'] ?? '') !== '' ? (string)$contact['uid'] : null);
                $customer->setNcContactLabel((string)$contact['label']);
                $customer->setNcContactSyncedAt($now);
                $customer->setCreatedAt($now);
                $customer->setUpdatedAt($now);
                $customer->setCreatedBy($this->users->getUser()?->getUID() ?? 'system');
                $this->customers->insert($customer);
                $this->activities->record('customer', $customer->getId(), 'imported_from_nextcloud', 'Kunde aus Nextcloud Kontakte importiert', $contact['label'] . ' · ' . $contact['addressBookName'], $customer->getId(), null);
                $created++;
            } catch (\Throwable) {
                $failed++;
            }
        }

        $message = sprintf('%d Kunden importiert, %d bereits vorhanden, %d fehlgeschlagen.', $created, $skipped, $failed);
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers') . '?message=' . rawurlencode($message));
    }



    #[NoAdminRequired]
    public function analyzeCustomerCsv(string $mapping = '{}'): JSONResponse {
        $this->permissions->assert('customers');
        try {
            $this->edition->assertLicensedPro('CSV-Kundenimport');
            [$headers,$rows] = $this->readCustomerCsv();
            $map = $this->decodeCsvMapping($mapping,$headers);
            $existing = $this->customerDuplicateIndex();
            $new=0;$duplicates=0;$invalid=0;$preview=[];
            foreach ($rows as $i=>$row) {
                $data=$this->mapCsvRow($headers,$row,$map);
                $reason=$this->csvInvalidReason($data);
                $dup=$reason===''?$this->csvDuplicateReason($data,$existing):'';
                if($reason!=='')$invalid++;elseif($dup!=='')$duplicates++;else{$new++;$this->addCsvDuplicateKeys($existing,$data);}
                if(count($preview)<12)$preview[]=['row'=>$i+2,'name'=>$this->csvDisplayName($data),'status'=>$reason!==''?'invalid':($dup!==''?'duplicate':'new'),'message'=>$reason!==''?$reason:($dup!==''?$dup:'Neu')];
            }
            return new JSONResponse(['ok'=>true,'headers'=>$headers,'total'=>count($rows),'new'=>$new,'duplicates'=>$duplicates,'invalid'=>$invalid,'preview'=>$preview]);
        } catch (\InvalidArgumentException $e) { return new JSONResponse(['ok'=>false,'message'=>$e->getMessage()],400); }
        catch (\Throwable $e) { return new JSONResponse(['ok'=>false,'message'=>'CSV konnte nicht geprüft werden.'],500); }
    }

    #[NoAdminRequired]
    public function importCustomerCsv(string $mapping = '{}'): JSONResponse {
        $this->permissions->assert('customers');
        try {
            $this->edition->assertLicensedPro('CSV-Kundenimport');
            [$headers,$rows] = $this->readCustomerCsv();
            $map=$this->decodeCsvMapping($mapping,$headers);$existing=$this->customerDuplicateIndex();
            $created=0;$duplicates=0;$invalid=0;$errors=[];
            foreach($rows as $i=>$row){
                $data=$this->mapCsvRow($headers,$row,$map);$reason=$this->csvInvalidReason($data);
                if($reason!==''){$invalid++;if(count($errors)<10)$errors[]='Zeile '.($i+2).': '.$reason;continue;}
                $dup=$this->csvDuplicateReason($data,$existing);if($dup!==''){$duplicates++;continue;}
                try{$customer=$this->createCsvCustomer($data);$created++;$this->addCsvDuplicateKeys($existing,$data);$this->activities->record('customer',$customer->getId(),'imported_from_csv','Kunde aus CSV importiert',$customer->getCustomerNo().' · '.$customer->getName(),$customer->getId(),null);}catch(\Throwable $e){$invalid++;if(count($errors)<10)$errors[]='Zeile '.($i+2).': '.$e->getMessage();}
            }
            return new JSONResponse(['ok'=>true,'created'=>$created,'duplicates'=>$duplicates,'invalid'=>$invalid,'errors'=>$errors,'message'=>sprintf('%d Kunden importiert, %d Dubletten übersprungen, %d fehlerhaft.',$created,$duplicates,$invalid)]);
        } catch (\InvalidArgumentException $e) { return new JSONResponse(['ok'=>false,'message'=>$e->getMessage()],400); }
        catch (\Throwable $e) { return new JSONResponse(['ok'=>false,'message'=>'CSV-Import fehlgeschlagen.'],500); }
    }

    /** @return array{0:array<int,string>,1:array<int,array<int,string>>} */
    private function readCustomerCsv(): array {
        $file=$this->request->getUploadedFile('csv');
        if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new \InvalidArgumentException('Bitte eine CSV-Datei auswählen.');
        if((int)($file['size']??0)>15*1024*1024)throw new \InvalidArgumentException('Die CSV-Datei darf maximal 15 MB groß sein.');
        $raw=file_get_contents((string)$file['tmp_name']);if($raw===false||trim($raw)==='')throw new \InvalidArgumentException('Die CSV-Datei ist leer.');
        if(str_starts_with($raw,"\xEF\xBB\xBF"))$raw=substr($raw,3);
        if(!mb_check_encoding($raw,'UTF-8'))$raw=mb_convert_encoding($raw,'UTF-8','Windows-1252,ISO-8859-1');
        $first=strtok($raw,"\r\n")?:'';$counts=[';'=>substr_count($first,';'),','=>substr_count($first,','),"\t"=>substr_count($first,"\t")];arsort($counts);$delimiter=(string)array_key_first($counts);if(($counts[$delimiter]??0)<1)throw new \InvalidArgumentException('CSV-Trennzeichen konnte nicht erkannt werden.');
        $h=fopen('php://temp','r+');fwrite($h,$raw);rewind($h);$headers=fgetcsv($h,0,$delimiter);if(!is_array($headers)||count($headers)<2)throw new \InvalidArgumentException('CSV-Kopfzeile konnte nicht gelesen werden.');$headers=array_map(static fn($v)=>trim((string)$v),$headers);
        $rows=[];while(($r=fgetcsv($h,0,$delimiter))!==false){if(count(array_filter($r,static fn($v)=>trim((string)$v)!==''))===0)continue;$rows[]=array_map(static fn($v)=>trim((string)$v),$r);if(count($rows)>10000)throw new \InvalidArgumentException('Maximal 10.000 Datensätze pro Import.');}fclose($h);return [$headers,$rows];
    }
    /** @return array<string,string> */
    private function decodeCsvMapping(string $json,array $headers):array{$m=json_decode($json,true);if(!is_array($m))throw new \InvalidArgumentException('Spaltenzuordnung ist ungültig.');$allowed=array_keys($this->csvFields());$out=[];foreach($m as $header=>$field){$header=(string)$header;$field=(string)$field;if(in_array($header,$headers,true)&&in_array($field,$allowed,true))$out[$header]=$field;}if($out===[])throw new \InvalidArgumentException('Bitte mindestens eine CSV-Spalte zuordnen.');return $out;}
    /** @return array<string,string> */
    private function csvFields():array{return ['customer_no'=>'Kundennummer','customer_type'=>'Kundentyp','name'=>'Firma / Kundenname','salutation'=>'Anrede','title'=>'Titel','first_name'=>'Vorname','last_name'=>'Nachname','address_addition'=>'Adresszusatz / c/o','contact_name'=>'Ansprechpartner','street'=>'Straße','postal_code'=>'PLZ','city'=>'Ort','country'=>'Land','phone'=>'Telefon','mobile'=>'Mobil','email'=>'E-Mail','invoice_email'=>'Rechnungs-E-Mail','vat_id'=>'USt-IdNr.','tax_no'=>'Steuernummer','leitweg_id'=>'Leitweg-ID','supplier_no'=>'Lieferantennummer','buyer_reference'=>'Käuferreferenz','purchase_order_reference'=>'Bestellreferenz','cost_center'=>'Kostenstelle','invoice_format'=>'Rechnungsformat','datev_debtor_account'=>'DATEV-Debitorenkonto','notes'=>'Notizen'];}
    /** @return array<string,string> */
    private function mapCsvRow(array $headers,array $row,array $map):array{$d=[];foreach($headers as $i=>$h){if(isset($map[$h]))$d[$map[$h]]=trim((string)($row[$i]??''));}return $d;}
    private function csvInvalidReason(array $d):string{$type=$this->normalizeCustomerType((string)($d['customer_type']??''),$d);$name=trim((string)($d['name']??''));$first=trim((string)($d['first_name']??''));$last=trim((string)($d['last_name']??''));if($type==='private'&&$name===''&&$first===''&&$last==='')return 'Name fehlt.';if($type!=='private'&&$name==='')return 'Firma / Kundenname fehlt.';$email=trim((string)($d['email']??''));if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))return 'E-Mail ist ungültig.';$invoice=trim((string)($d['invoice_email']??''));if($invoice!==''&&!filter_var($invoice,FILTER_VALIDATE_EMAIL))return 'Rechnungs-E-Mail ist ungültig.';return '';}
    private function csvDisplayName(array $d):string{$name=trim((string)($d['name']??''));if($name!=='')return $name;return trim(implode(' ',array_filter([(string)($d['title']??''),(string)($d['first_name']??''),(string)($d['last_name']??'')])));}
    /** @return array<string,bool> */
    private function customerDuplicateIndex():array{$idx=[];foreach($this->customers->findAllActive() as $c){foreach([(string)$c->getCustomerNo(),(string)$c->getEmail(),(string)$c->getInvoiceEmail(),(string)$c->getVatId()] as $v){$k=$this->duplicateKey($v);if($k!=='')$idx[$k]=true;}}return $idx;}
    private function csvDuplicateReason(array $d,array $idx):string{foreach(['customer_no'=>'Kundennummer','email'=>'E-Mail','invoice_email'=>'Rechnungs-E-Mail','vat_id'=>'USt-IdNr.'] as $f=>$label){$k=$this->duplicateKey((string)($d[$f]??''));if($k!==''&&isset($idx[$k]))return $label.' bereits vorhanden.';}return '';}
    private function addCsvDuplicateKeys(array &$idx,array $d):void{foreach(['customer_no','email','invoice_email','vat_id'] as $f){$k=$this->duplicateKey((string)($d[$f]??''));if($k!=='')$idx[$k]=true;}}
    private function duplicateKey(string $v):string{$v=mb_strtolower(trim($v));return $v===''?'':$v;}
    private function normalizeCustomerType(string $v,array $d=[]):string{$v=mb_strtolower(trim($v));if(in_array($v,['privat','privatkunde','private','person'],true))return 'private';if(in_array($v,['öffentlich','oeffentlich','public','behörde','behoerde'],true))return 'public';if(in_array($v,['firma','unternehmen','business','company'],true))return 'business';return trim((string)($d['name']??''))!==''?'business':'private';}
    private function createCsvCustomer(array $d):Customer{$type=$this->normalizeCustomerType((string)($d['customer_type']??''),$d);$name=trim((string)($d['name']??''));if($name===''&&$type==='private')$name=$this->csvDisplayName($d);$number=trim((string)($d['customer_no']??''));if($number==='')$number=$this->numbers->next('customer');$now=new \DateTime();$c=new Customer();$c->setCustomerNo($number);$c->setName($name);$c->setCustomerType($type);$c->setSalutation($this->nullable((string)($d['salutation']??'')));$c->setTitle($this->nullable((string)($d['title']??'')));$c->setFirstName($this->nullable((string)($d['first_name']??'')));$c->setLastName($this->nullable((string)($d['last_name']??'')));$c->setAddressAddition($this->nullable((string)($d['address_addition']??'')));$c->setContactName($this->nullable((string)($d['contact_name']??'')));$c->setStreet($this->nullable((string)($d['street']??'')));$c->setPostalCode($this->nullable((string)($d['postal_code']??'')));$c->setCity($this->nullable((string)($d['city']??'')));$c->setCountry($this->nullable((string)($d['country']??'')));$c->setPhone($this->nullable((string)($d['phone']??'')));$c->setMobile($this->nullable((string)($d['mobile']??'')));$c->setEmail($this->nullable((string)($d['email']??'')));$c->setInvoiceEmail($this->nullable((string)($d['invoice_email']??'')));$c->setVatId($this->nullable((string)($d['vat_id']??'')));$c->setTaxNo($this->nullable((string)($d['tax_no']??'')));$c->setLeitwegId($this->nullable((string)($d['leitweg_id']??'')));$c->setSupplierNo($this->nullable((string)($d['supplier_no']??'')));$c->setBuyerReference($this->nullable((string)($d['buyer_reference']??'')));$c->setPurchaseOrderReference($this->nullable((string)($d['purchase_order_reference']??'')));$c->setCostCenter($this->nullable((string)($d['cost_center']??'')));$fmt=mb_strtolower(trim((string)($d['invoice_format']??'')));$c->setInvoiceFormat(in_array($fmt,['pdf','xrechnung','zugferd'],true)?$fmt:'pdf');$da=preg_replace('/[^0-9]/','',(string)($d['datev_debtor_account']??''));$c->setDatevDebtorAccount($da!==''?$da:null);$c->setNotes($this->nullable((string)($d['notes']??'')));$address=array_filter([trim((string)($d['street']??'')),trim(trim((string)($d['postal_code']??'')).' '.trim((string)($d['city']??''))),trim((string)($d['country']??''))]);$c->setAddress($address?implode("\n",$address):null);$c->setFolderPath($this->folders->ensureCustomerFolder($number,$name));$c->setCreatedAt($now);$c->setUpdatedAt($now);$c->setCreatedBy($this->users->getUser()?->getUID()??'system');$this->customers->insert($c);return $c;}

    #[NoAdminRequired]
    public function linkCustomerContact(int $customerId, string $addressBookKey, string $contactId): RedirectResponse {
        $this->permissions->assert('customers');
        $contact = $this->integration->findContact($addressBookKey, $contactId);
        if ($contact === null) {
            throw new \InvalidArgumentException('Der Nextcloud-Kontakt wurde nicht gefunden.');
        }
        $customer = $this->customers->find($customerId);
        $customer->setNcAddressbookKey($addressBookKey);
        $customer->setNcContactId($contactId);
        $customer->setNcContactUid($contact['uid'] !== '' ? $contact['uid'] : null);
        $customer->setNcContactLabel($contact['label']);
        $customer->setNcContactSyncedAt(new \DateTime());
        $this->applyContactData($customer, $contact);
        $customer->setUpdatedAt(new \DateTime());
        $this->customers->update($customer);
        $this->activities->record('customer', $customerId, 'nextcloud_contact_linked', 'Nextcloud-Kontakt verbunden', $contact['label'] . ' · ' . $contact['addressBookName'], $customerId, null);
        return $this->redirectToCustomer($customerId);
    }

    #[NoAdminRequired]
    public function syncCustomerContact(int $customerId): RedirectResponse {
        $this->permissions->assert('customers');
        $customer = $this->customers->find($customerId);
        $addressBookKey = (string)($customer->getNcAddressbookKey() ?? '');
        $contactId = (string)($customer->getNcContactId() ?? '');
        if ($addressBookKey === '' || $contactId === '') {
            throw new \InvalidArgumentException('Dieser Kunde ist noch nicht mit einem Nextcloud-Kontakt verbunden.');
        }
        $contact = $this->integration->findContact($addressBookKey, $contactId);
        if ($contact === null) {
            throw new \InvalidArgumentException('Der verbundene Nextcloud-Kontakt ist nicht mehr verfügbar.');
        }
        $this->applyContactData($customer, $contact);
        $customer->setNcContactUid($contact['uid'] !== '' ? $contact['uid'] : null);
        $customer->setNcContactLabel($contact['label']);
        $customer->setNcContactSyncedAt(new \DateTime());
        $customer->setUpdatedAt(new \DateTime());
        $this->customers->update($customer);
        $this->activities->record('customer', $customerId, 'nextcloud_contact_synced', 'Nextcloud-Kontakt synchronisiert', $contact['label'], $customerId, null);
        return $this->redirectToCustomer($customerId);
    }

    #[NoAdminRequired]
    public function unlinkCustomerContact(int $customerId): RedirectResponse {
        $this->permissions->assert('customers');
        $customer = $this->customers->find($customerId);
        $label = (string)($customer->getNcContactLabel() ?? '');
        $customer->setNcAddressbookKey(null);
        $customer->setNcContactId(null);
        $customer->setNcContactUid(null);
        $customer->setNcContactLabel(null);
        $customer->setNcContactSyncedAt(null);
        $customer->setUpdatedAt(new \DateTime());
        $this->customers->update($customer);
        $this->activities->record('customer', $customerId, 'nextcloud_contact_unlinked', 'Nextcloud-Kontakt getrennt', $label, $customerId, null);
        return $this->redirectToCustomer($customerId);
    }


    #[NoAdminRequired]
    public function repair(): RedirectResponse {
        $this->permissions->assert('settings');
        try {
            $status = $this->integration->repairIntegration();
            $repair = $status['calendarRepair'] ?? [];
            $message = (string)($repair['message'] ?? 'Integration geprüft.');
            if (($status['contactCount'] ?? 0) === 0) {
                $message .= ' Es wurden aktuell keine Kontakte gefunden.';
            }
            return new RedirectResponse($this->url->linkToRoute('reinhardterp.integration.index') . '?success=' . rawurlencode($message));
        } catch (\Throwable $e) {
            return new RedirectResponse($this->url->linkToRoute('reinhardterp.integration.index') . '?error=' . rawurlencode($e->getMessage()));
        }
    }

    #[NoAdminRequired]
    public function saveCalendarSettings(?string $calendarKey = null): RedirectResponse {
        $this->permissions->assert('settings');
        $this->integration->saveCalendarSelection((string)$calendarKey);
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.module.settings'));
    }

    #[NoAdminRequired]
    public function syncCalendar(): RedirectResponse {
        $this->permissions->assert('calendar');
        try {
            $stats = $this->integration->syncCalendarEvents();
            $message = sprintf('%d Termine gelesen, %d neu, %d aktualisiert, %d entfernt.', $stats['total'], $stats['imported'], $stats['updated'], $stats['removed']);
            return new RedirectResponse($this->url->linkToRoute('reinhardterp.module.teamEvents') . '?success=' . rawurlencode($message));
        } catch (\Throwable $e) {
            return new RedirectResponse($this->url->linkToRoute('reinhardterp.module.teamEvents') . '?error=' . rawurlencode($e->getMessage()));
        }
    }

    /** @param array<string,mixed> $contact */
    private function applyContactData(Customer $customer, array $contact): void {
        if ($contact['organisation'] !== '') {
            $customer->setName($contact['organisation']);
        } elseif ($contact['fullName'] !== '') {
            $customer->setName($contact['fullName']);
        }
        if ($contact['organisation'] !== '' && $contact['fullName'] !== '') {
            $customer->setContactName($contact['fullName']);
        }
        if ($contact['phone'] !== '') {
            $customer->setPhone($contact['phone']);
        }
        if (($contact['mobile'] ?? '') !== '') {
            $customer->setMobile($contact['mobile']);
        }
        if ($contact['email'] !== '') {
            $customer->setEmail($contact['email']);
        }
        if (($contact['street'] ?? '') !== '') { $customer->setStreet($contact['street']); }
        if (($contact['postalCode'] ?? '') !== '') { $customer->setPostalCode($contact['postalCode']); }
        if (($contact['city'] ?? '') !== '') { $customer->setCity($contact['city']); }
        if (($contact['country'] ?? '') !== '') { $customer->setCountry($contact['country']); }
        if ($contact['address'] !== '') { $customer->setAddress($contact['address']); }
    }

    private function redirectToCustomer(int $customerId): RedirectResponse {
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customerDetail', ['id' => $customerId]));
    }

    private function nullable(string $value): ?string {
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
