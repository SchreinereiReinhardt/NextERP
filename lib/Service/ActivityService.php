<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;
use OCP\IUserManager;
use OCP\IUserSession;

final class ActivityService {
    public function __construct(
        private IDBConnection $db,
        private IUserSession $session,
        private IUserManager $userManager,
    ) {}

    public function record(
        string $entityType,
        ?int $entityId,
        string $action,
        string $title,
        ?string $details = null,
        ?int $customerId = null,
        ?int $projectId = null,
    ): void {
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_activities')
            ->values([
                'customer_id' => $qb->createNamedParameter($customerId),
                'project_id' => $qb->createNamedParameter($projectId),
                'entity_type' => $qb->createNamedParameter($entityType),
                'entity_id' => $qb->createNamedParameter($entityId),
                'action' => $qb->createNamedParameter($action),
                'title' => $qb->createNamedParameter(mb_substr(trim($title), 0, 255)),
                'details' => $qb->createNamedParameter($details !== null && trim($details) !== '' ? trim($details) : null),
                'created_by' => $qb->createNamedParameter($this->session->getUser()?->getUID() ?? 'system'),
                'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
            ])
            ->executeStatement();
    }

    public function forProject(int $projectId, int $limit = 100): array {
        return $this->find('project_id', $projectId, $limit);
    }

    public function forCustomer(int $customerId, int $limit = 100): array {
        return $this->find('customer_id', $customerId, $limit);
    }

    public function recent(int $limit = 15): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_activities')->orderBy('created_at', 'DESC')->setMaxResults($limit);
        return $this->decorate($qb->executeQuery()->fetchAllAssociative());
    }

    private function find(string $column, int $id, int $limit): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_activities')
            ->where($qb->expr()->eq($column, $qb->createNamedParameter($id)))
            ->orderBy('created_at', 'DESC')->setMaxResults($limit);
        return $this->decorate($qb->executeQuery()->fetchAllAssociative());
    }

    private function decorate(array $rows): array {
        foreach ($rows as &$row) {
            $user = $this->userManager->get((string)$row['created_by']);
            $row['display_name'] = $user?->getDisplayName() ?? (string)$row['created_by'];
        }
        unset($row);
        return $rows;
    }
}
