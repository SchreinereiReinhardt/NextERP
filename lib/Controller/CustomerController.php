<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Db\Customer;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Service\ActivityService;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\NextcloudIntegrationService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserSession;

final class CustomerController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private CustomerMapper $mapper,
        private IUserSession $users,
        private IURLGenerator $url,
        private FolderService $folders,
        private NumberService $numbers,
        private PermissionService $permissions,
        private ActivityService $activities,
        private NextcloudIntegrationService $integration,
        private IDBConnection $db,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function save(
        ?int $id,
        string $name,
        ?string $customerNo = null,
        ?string $contactName = null,
        ?string $phone = null,
        ?string $mobile = null,
        ?string $email = null,
        ?string $street = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $country = null,
        ?string $address = null,
        ?string $notes = null,
        ?string $customerType = null,
        ?string $salutation = null,
        ?string $title = null,
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $addressAddition = null,
        ?string $invoiceEmail = null,
        ?string $vatId = null,
        ?string $taxNo = null,
        ?string $leitwegId = null,
        ?string $supplierNo = null,
        ?string $buyerReference = null,
        ?string $purchaseOrderReference = null,
        ?string $costCenter = null,
        ?string $invoiceFormat = null,
        ?string $datevDebtorAccount = null,
        ?string $saveToNextcloudContacts = null,
        ?string $addressBookKey = null,
    ): RedirectResponse {
        $this->permissions->assert('customers');
        $name = trim($name);
        $type = in_array($customerType, ['private','business','public'], true) ? $customerType : 'business';
        $isNew = $id === null;
        if ($name === '' && $type === 'private') {
            $parts = array_values(array_filter([trim((string)$title), trim((string)$firstName), trim((string)$lastName)], static fn(string $v): bool => $v !== ''));
            $name = implode(' ', $parts);
        }
        if ($name === '') {
            throw new \InvalidArgumentException($type === 'private' ? 'Vorname oder Nachname ist Pflicht.' : 'Firma / Kundenname ist Pflicht.');
        }

        $now = new \DateTime();
        $customer = $isNew ? new Customer() : $this->mapper->find((int)$id);
        $customer->setName($name);
        $number = $isNew ? $this->numbers->next('customer') : (string)$customer->getCustomerNo();
        $customer->setCustomerNo($number);
        $customer->setContactName($this->nullable($contactName));
        $customer->setPhone($this->nullable($phone));
        $customer->setMobile($this->nullable($mobile));
        $customer->setEmail($this->nullable($email));
        $customer->setStreet($this->nullable($street));
        $customer->setPostalCode($this->nullable($postalCode));
        $customer->setCity($this->nullable($city));
        $customer->setCountry($this->nullable($country));
        $customer->setAddress($this->composeAddress($street, $postalCode, $city, $country, $address));
        $customer->setNotes($this->nullable($notes));
        $customer->setCustomerType($type);
        $customer->setSalutation($this->nullable($salutation));
        $customer->setTitle($this->nullable($title));
        $customer->setFirstName($this->nullable($firstName));
        $customer->setLastName($this->nullable($lastName));
        $customer->setAddressAddition($this->nullable($addressAddition));
        $customer->setInvoiceEmail($this->nullable($invoiceEmail));
        $customer->setVatId($this->nullable($vatId));
        $customer->setTaxNo($this->nullable($taxNo));
        $customer->setLeitwegId($this->nullable($leitwegId));
        $customer->setSupplierNo($this->nullable($supplierNo));
        $customer->setBuyerReference($this->nullable($buyerReference));
        $customer->setPurchaseOrderReference($this->nullable($purchaseOrderReference));
        $customer->setCostCenter($this->nullable($costCenter));
        $customer->setInvoiceFormat(in_array($invoiceFormat, ['pdf','xrechnung','zugferd'], true) ? $invoiceFormat : 'pdf');
        $da=preg_replace('/[^0-9]/','',(string)$datevDebtorAccount);$customer->setDatevDebtorAccount($da!==''?$da:null);
        $customer->setUpdatedAt($now);
        $customer->setFolderPath($this->folders->ensureCustomerFolder($number, $name));

        if ($isNew) {
            $customer->setCreatedAt($now);
            $customer->setCreatedBy($this->users->getUser()?->getUID() ?? 'system');
            $this->mapper->insert($customer);
            $this->activities->record('customer', $customer->getId(), 'created', 'Kunde erstellt', $number . ' · ' . $name, $customer->getId(), null);
        } else {
            $this->mapper->update($customer);
            $this->activities->record('customer', $customer->getId(), 'updated', 'Kundendaten geändert', $number . ' · ' . $name, $customer->getId(), null);
        }

        $message = '';
        try {
            if ($customer->getNcContactId()) {
                $contact = $this->integration->updateCustomerContact(
                    (string)$customer->getNcAddressbookKey(),
                    (string)$customer->getNcContactId(),
                    $customer->getName(),
                    $customer->getContactName(),
                    $customer->getPhone(),
                    $customer->getMobile(),
                    $customer->getEmail(),
                    $customer->getStreet(),
                    $customer->getPostalCode(),
                    $customer->getCity(),
                    $customer->getCountry(),
                    $customer->getNcContactUid(),
                );
                $this->storeContactLink($customer, $contact);
                $message = 'Kunde und Nextcloud-Kontakt wurden aktualisiert.';
            } elseif ($saveToNextcloudContacts !== null) {
                $contact = $this->integration->createCustomerContact(
                    (string)$addressBookKey,
                    $customer->getName(),
                    $customer->getContactName(),
                    $customer->getPhone(),
                    $customer->getMobile(),
                    $customer->getEmail(),
                    $customer->getStreet(),
                    $customer->getPostalCode(),
                    $customer->getCity(),
                    $customer->getCountry(),
                );
                $this->storeContactLink($customer, $contact);
                $this->activities->record('customer', $customer->getId(), 'nextcloud_contact_created', 'Nextcloud-Kontakt erstellt', $contact['label'], $customer->getId(), null);
                $message = 'Kunde wurde gespeichert und in Nextcloud Kontakte angelegt.';
            }
        } catch (\Throwable $e) {
            $message = 'Kunde wurde gespeichert. Nextcloud-Kontakt konnte nicht gespeichert werden: ' . $e->getMessage();
        }

        $target = $this->url->linkToRoute('reinhardterp.page.customerDetail', ['id' => $customer->getId()]);
        if ($message !== '') {
            $target .= '?message=' . rawurlencode($message);
        }
        return new RedirectResponse($target);
    }

    #[NoAdminRequired]
    public function quickCreate(
        string $name,
        ?string $contactName = null,
        ?string $phone = null,
        ?string $email = null,
        ?string $street = null,
        ?string $postalCode = null,
        ?string $city = null,
        ?string $country = 'Deutschland',
    ): JSONResponse {
        $this->permissions->assert('customers');
        $name = trim($name);
        if ($name === '') {
            return new JSONResponse(['ok' => false, 'message' => 'Firma / Kundenname ist Pflicht.'], 400);
        }
        try {
            $now = new \DateTime();
            $number = $this->numbers->next('customer');
            $customer = new Customer();
            $customer->setName($name);
            $customer->setCustomerNo($number);
            $customer->setContactName($this->nullable($contactName));
            $customer->setPhone($this->nullable($phone));
            $customer->setEmail($this->nullable($email));
            $customer->setStreet($this->nullable($street));
            $customer->setPostalCode($this->nullable($postalCode));
            $customer->setCity($this->nullable($city));
            $customer->setCountry($this->nullable($country));
            $customer->setAddress($this->composeAddress($street, $postalCode, $city, $country));
            $customer->setCustomerType('business');
            $customer->setInvoiceFormat('pdf');
            $customer->setCreatedAt($now);
            $customer->setUpdatedAt($now);
            $customer->setCreatedBy($this->users->getUser()?->getUID() ?? 'system');
            $customer->setFolderPath($this->folders->ensureCustomerFolder($number, $name));
            $this->mapper->insert($customer);
            $this->activities->record('customer', $customer->getId(), 'created', 'Kunde erstellt', $number . ' · ' . $name, $customer->getId(), null);
            return new JSONResponse(['ok' => true, 'customer' => ['id' => $customer->getId(), 'name' => $customer->getName(), 'customerNo' => $customer->getCustomerNo()]]);
        } catch (\Throwable $e) {
            return new JSONResponse(['ok' => false, 'message' => 'Kunde konnte nicht gespeichert werden.'], 500);
        }
    }

    #[NoAdminRequired]
    public function anonymize(int $id): RedirectResponse {
        $this->permissions->assert('customers');
        if ($this->permissions->role() !== PermissionService::ADMIN) {
            throw new \OCP\AppFramework\Http\ForbiddenException('Nur Administratoren dürfen Kundendaten anonymisieren.');
        }
        $customer = $this->mapper->find($id);
        $number = trim((string)$customer->getCustomerNo());
        $label = $number !== '' ? 'Anonymisierter Kunde '.$number : 'Anonymisierter Kunde #'.$id;
        $customer->setName($label);
        foreach (['ContactName','Phone','Mobile','Email','Address','Street','PostalCode','City','Country','Notes','Salutation','Title','FirstName','LastName','AddressAddition','InvoiceEmail','VatId','TaxNo','LeitwegId','SupplierNo','BuyerReference','PurchaseOrderReference','CostCenter','DatevDebtorAccount','NcAddressbookKey','NcContactId','NcContactUid','NcContactLabel'] as $field) {
            $setter = 'set'.$field;
            $customer->$setter(null);
        }
        $customer->setNcContactSyncedAt(null);
        $customer->setCustomerType('private');
        $customer->setInvoiceFormat('pdf');
        $customer->setIsArchived(true);
        $customer->setUpdatedAt(new \DateTime());
        $this->mapper->update($customer);
        foreach (['re_erp_customer_contacts','re_erp_customer_reminders'] as $table) {
            $this->deleteCustomerRows($table, $id);
        }
        // Audit-Einträge bleiben erhalten, aber die bisherige Bezeichnung wird aus den Details entfernt.
        $qb=$this->db->getQueryBuilder();
        $qb->update('re_erp_activities')->set('details',$qb->createNamedParameter(null))->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($id)))->executeStatement();
        $this->activities->record('customer', $id, 'anonymized', 'Kundendaten anonymisiert', null, $id, null);
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers').'?message='.rawurlencode('Kundendaten wurden anonymisiert. Geschäftsvorgänge und aufbewahrungspflichtige Belege bleiben erhalten.'));
    }

    #[NoAdminRequired]
    public function deleteCustomer(int $id): RedirectResponse {
        $this->permissions->assert('customers');
        if ($this->permissions->role() !== PermissionService::ADMIN) {
            throw new \OCP\AppFramework\Http\ForbiddenException('Nur Administratoren dürfen Kunden endgültig löschen.');
        }
        $customer = $this->mapper->find($id);
        $references = $this->customerReferences($id);
        if ($references !== []) {
            $parts=[]; foreach($references as $label=>$count){$parts[]=$label.': '.$count;}
            $msg='Endgültiges Löschen nicht möglich. Zum Kunden bestehen Geschäftsvorgänge ('.implode(', ',$parts).'). Nutzen Sie stattdessen „Kundendaten anonymisieren“.';
            return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$id]).'?message='.rawurlencode($msg));
        }
        $folderPath=trim((string)($customer->getFolderPath()??''),'/');
        foreach (['re_erp_customer_contacts','re_erp_customer_reminders','re_erp_activities'] as $table) {
            $this->deleteCustomerRows($table, $id);
        }
        $this->mapper->delete($customer);
        if($folderPath!=='') { try { $this->folders->deletePath($folderPath); } catch (\Throwable) {} }
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers').'?message='.rawurlencode('Kunde wurde endgültig gelöscht.'));
    }

    /** @return array<string,int> */
    private function customerReferences(int $id): array {
        $tables=[
            're_erp_projects'=>'Projekte','re_erp_reports'=>'Rapporte','re_erp_offers'=>'Angebote',
            're_erp_orders'=>'Aufträge','re_erp_delivery_notes'=>'Lieferscheine','re_erp_invoices'=>'Rechnungen',
            're_erp_billing_batches'=>'Abrechnungsvorbereitungen','re_erp_communications'=>'Kommunikation',
            're_erp_workday_entries'=>'Zeiterfassungen','re_erp_project_documents'=>'Projektdokumente',
            're_erp_documents'=>'Dokumente','re_erp_team_events'=>'Termine','re_erp_stock_movements'=>'Lagerbewegungen',
            're_erp_document_rules'=>'Dokumentenregeln',
        ];
        $out=[];
        foreach($tables as $table=>$label){
            try {
                $qb=$this->db->getQueryBuilder();
                $qb->selectAlias($qb->createFunction('COUNT(*)'),'cnt')->from($table)->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($id)));
                $result=$qb->executeQuery(); $row=$result->fetch(); $result->closeCursor();
                $count=(int)($row['cnt']??0); if($count>0)$out[$label]=$count;
            } catch (\Throwable) { /* optionale/ältere Tabellen dürfen fehlen */ }
        }
        return $out;
    }

    private function deleteCustomerRows(string $table,int $id): void {
        try { $qb=$this->db->getQueryBuilder();$qb->delete($table)->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($id)))->executeStatement(); } catch (\Throwable) {}
    }

    #[NoAdminRequired]
    public function archive(int $id): RedirectResponse {
        $this->permissions->assert('customers');
        $customer = $this->mapper->find($id);
        $customer->setIsArchived(true);
        $customer->setUpdatedAt(new \DateTime());
        $this->mapper->update($customer);
        $this->activities->record('customer', $id, 'archived', 'Kunde archiviert', $customer->getCustomerNo() . ' · ' . $customer->getName(), $id, null);
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customers'));
    }

    /** @param array<string,mixed> $contact */
    private function storeContactLink(Customer $customer, array $contact): void {
        $customer->setNcAddressbookKey((string)$contact['addressBookKey']);
        $customer->setNcContactId((string)$contact['id']);
        $customer->setNcContactUid(($contact['uid'] ?? '') !== '' ? (string)$contact['uid'] : null);
        $customer->setNcContactLabel((string)$contact['label']);
        $customer->setNcContactSyncedAt(new \DateTime());
        $customer->setUpdatedAt(new \DateTime());
        $this->mapper->update($customer);
    }

    private function composeAddress(?string $street, ?string $postalCode, ?string $city, ?string $country, ?string $legacyAddress = null): ?string {
        $street = trim((string)$street);
        $postalCode = trim((string)$postalCode);
        $city = trim((string)$city);
        $country = trim((string)$country);
        $lines = [];
        if ($street !== '') {
            $lines[] = $street;
        }
        $place = trim($postalCode . ' ' . $city);
        if ($place !== '') {
            $lines[] = $place;
        }
        if ($country !== '') {
            $lines[] = $country;
        }
        if ($lines === [] && trim((string)$legacyAddress) !== '') {
            return trim((string)$legacyAddress);
        }
        return $lines === [] ? null : implode("\n", $lines);
    }

    private function nullable(?string $value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim($value);
        return $value === '' ? null : $value;
    }
}
