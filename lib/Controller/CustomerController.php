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
use OCP\IRequest;
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
        ?string $saveToNextcloudContacts = null,
        ?string $addressBookKey = null,
    ): RedirectResponse {
        $this->permissions->assert('customers');
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Name ist Pflicht.');
        }

        $now = new \DateTime();
        $isNew = $id === null;
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
