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
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
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
