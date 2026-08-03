<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\ActivityService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

final class CustomerWorkspaceController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IDBConnection $db,
        private IURLGenerator $url,
        private IUserSession $session,
        private PermissionService $permissions,
        private ActivityService $activities,
    ) { parent::__construct($appName, $request); }

    #[NoAdminRequired]
    public function addContact(int $customerId, string $name, ?string $position=null, ?string $phone=null, ?string $mobile=null, ?string $email=null, ?string $notes=null, bool $isPrimary=false): RedirectResponse {
        $this->permissions->assert('customers');
        $name = trim($name);
        if ($name === '') throw new \InvalidArgumentException('Name des Ansprechpartners fehlt.');
        if ($isPrimary) $this->clearPrimary($customerId);
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_customer_contacts')->values([
            'customer_id' => $qb->createNamedParameter($customerId),
            'name' => $qb->createNamedParameter($name),
            'position' => $qb->createNamedParameter($this->nullable($position)),
            'phone' => $qb->createNamedParameter($this->nullable($phone)),
            'mobile' => $qb->createNamedParameter($this->nullable($mobile)),
            'email' => $qb->createNamedParameter($this->nullable($email)),
            'notes' => $qb->createNamedParameter($this->nullable($notes)),
            'is_primary' => $qb->createNamedParameter($isPrimary ? 1 : 0),
            'created_by' => $qb->createNamedParameter($this->uid()),
            'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
        ])->executeStatement();
        $this->activities->record('customer', $customerId, 'contact_added', 'Ansprechpartner hinzugefügt', $name, $customerId, null);
        return $this->back($customerId);
    }

    #[NoAdminRequired]
    public function deleteContact(int $customerId, int $id): RedirectResponse {
        $this->permissions->assert('customers');
        $qb = $this->db->getQueryBuilder();
        $qb->delete('re_erp_customer_contacts')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->andWhere($qb->expr()->eq('customer_id', $qb->createNamedParameter($customerId)))->executeStatement();
        $this->activities->record('customer', $customerId, 'contact_deleted', 'Ansprechpartner entfernt', null, $customerId, null);
        return $this->back($customerId);
    }

    #[NoAdminRequired]
    public function addReminder(int $customerId, string $title, string $dueDate, ?string $notes=null): RedirectResponse {
        $this->permissions->assert('customers');
        $title = trim($title);
        if ($title === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate)) throw new \InvalidArgumentException('Wiedervorlage unvollständig.');
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_customer_reminders')->values([
            'customer_id' => $qb->createNamedParameter($customerId),
            'title' => $qb->createNamedParameter($title),
            'due_date' => $qb->createNamedParameter($dueDate),
            'notes' => $qb->createNamedParameter($this->nullable($notes)),
            'is_done' => $qb->createNamedParameter(0),
            'created_by' => $qb->createNamedParameter($this->uid()),
            'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
        ])->executeStatement();
        $this->activities->record('customer', $customerId, 'reminder_added', 'Wiedervorlage angelegt', $title.' · '.$dueDate, $customerId, null);
        return $this->back($customerId);
    }

    #[NoAdminRequired]
    public function toggleReminder(int $customerId, int $id): RedirectResponse {
        $this->permissions->assert('customers');
        $qb = $this->db->getQueryBuilder();
        $qb->select('is_done', 'title')->from('re_erp_customer_reminders')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->andWhere($qb->expr()->eq('customer_id', $qb->createNamedParameter($customerId)));
        $row = $qb->executeQuery()->fetchAssociative();
        if (!$row) return $this->back($customerId);
        $done = !(bool)$row['is_done'];
        $qb = $this->db->getQueryBuilder();
        $qb->update('re_erp_customer_reminders')->set('is_done', $qb->createNamedParameter($done ? 1 : 0))->set('completed_at', $qb->createNamedParameter($done ? date('Y-m-d H:i:s') : null))->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeStatement();
        $this->activities->record('customer', $customerId, $done ? 'reminder_done' : 'reminder_reopened', $done ? 'Wiedervorlage erledigt' : 'Wiedervorlage wieder geöffnet', (string)$row['title'], $customerId, null);
        return $this->back($customerId);
    }

    private function clearPrimary(int $customerId): void { $qb=$this->db->getQueryBuilder(); $qb->update('re_erp_customer_contacts')->set('is_primary',$qb->createNamedParameter(0))->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($customerId)))->executeStatement(); }
    private function nullable(?string $value): ?string { $value=trim((string)$value); return $value===''?null:$value; }
    private function uid(): string { return $this->session->getUser()?->getUID() ?? 'system'; }
    private function back(int $customerId): RedirectResponse { return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customerDetail', ['id'=>$customerId])); }
}
