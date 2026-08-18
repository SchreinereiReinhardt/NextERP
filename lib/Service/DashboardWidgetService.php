<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\Dashboard\Model\WidgetItem;
use OCP\IDBConnection;
use OCP\IURLGenerator;

final class DashboardWidgetService {
    public function __construct(
        private IDBConnection $db,
        private PermissionService $permissions,
        private IURLGenerator $url,
    ) {}

    /** @return list<WidgetItem> */
    public function today(string $userId, int $limit = 7): array {
        if (!$this->permissions->isEnabled($userId)) {
            return [];
        }
        $items = [];
        if ($this->permissions->can('time')) {
            $hours = $this->todayHours($userId);
            $items[] = $this->item(
                number_format($hours, 2, ',', '.') . ' Stunden heute',
                $this->permissions->isProjectSupervisor($userId) ? 'Erfasste Arbeitszeit im Betrieb' : 'Deine heute erfasste Arbeitszeit',
                'reinhardterp.module.workdays',
                'today-hours'
            );
        }
        if ($this->permissions->can('reports')) {
            $open = $this->openReports($userId);
            $items[] = $this->item(
                $open . ' offene Rapporte',
                $open === 1 ? 'Ein Rapport wartet auf Abschluss' : 'Rapporte warten auf Abschluss oder Unterschrift',
                'reinhardterp.module.reports',
                'open-reports'
            );
        }
        if ($this->permissions->can('calendar')) {
            $event = $this->nextEvent();
            if ($event !== null) {
                $start = new \DateTimeImmutable((string)$event['start_at']);
                $items[] = $this->item(
                    (string)$event['title'],
                    $start->format('d.m.Y · H:i') . ' Uhr' . (!empty($event['location']) ? ' · ' . $event['location'] : ''),
                    'reinhardterp.module.teamEvents',
                    'event-' . (string)($event['id'] ?? $start->getTimestamp())
                );
            }
        }
        return array_slice($items, 0, $limit);
    }

    /** @return list<WidgetItem> */
    public function attention(string $userId, int $limit = 7): array {
        if (!$this->permissions->isEnabled($userId) || !$this->permissions->can('dashboard')) {
            return [];
        }
        $items = [];
        $overdue = $this->overdueProjects($userId);
        if ($overdue > 0) {
            $items[] = $this->item($overdue . ' Projekte über Termin', 'Aktive Projekte mit überschrittenem Fälligkeitsdatum', 'reinhardterp.page.projects', 'overdue-projects');
        }
        if ($this->permissions->can('reports')) {
            $open = $this->openReports($userId);
            if ($open > 0) {
                $items[] = $this->item($open . ' offene Rapporte', 'Abschluss oder Unterschrift noch ausstehend', 'reinhardterp.module.reports', 'attention-reports');
            }
        }
        if ($this->permissions->can('documents')) {
            $documents = $this->unassignedDocuments();
            if ($documents > 0) {
                $items[] = $this->item($documents . ' Dokumente bearbeiten', 'Neue oder noch nicht vollständig zugeordnete Dokumente', 'reinhardterp.document.index', 'attention-documents');
            }
        }
        if ($this->permissions->can('inventory')) {
            $stock = $this->lowStock();
            if ($stock > 0) {
                $items[] = $this->item($stock . ' Lagerpositionen prüfen', 'Mindestbestand erreicht oder unterschritten', 'reinhardterp.business.inventory', 'attention-stock');
            }
        }
        return array_slice($items, 0, $limit);
    }

    /** @return list<WidgetItem> */
    public function projects(string $userId, int $limit = 7): array {
        if (!$this->permissions->isEnabled($userId) || !$this->permissions->can('projects')) {
            return [];
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'project_no', 'title', 'status', 'due_date', 'updated_at')
            ->from('re_erp_projects')
            ->where($qb->expr()->eq('is_archived', $qb->createNamedParameter(false, $qb::PARAM_BOOL)))
            ->orderBy('updated_at', 'DESC')
            ->setMaxResults(40);
        $rows = $qb->executeQuery()->fetchAllAssociative();
        $items = [];
        foreach ($rows as $row) {
            $id = (int)$row['id'];
            if (!$this->permissions->canAccessProject($id, $userId)) {
                continue;
            }
            $subtitle = 'Status: ' . ((string)$row['status'] !== '' ? (string)$row['status'] : 'offen');
            if (!empty($row['due_date'])) {
                $subtitle .= ' · Fällig ' . date('d.m.Y', strtotime((string)$row['due_date']));
            }
            $items[] = new WidgetItem(
                trim((string)$row['project_no'] . ' · ' . (string)$row['title'], ' ·'),
                $subtitle,
                $this->url->linkToRouteAbsolute('reinhardterp.page.projectDetail', ['id' => $id]),
                $this->iconUrl(),
                'project-' . $id . '-' . (string)($row['updated_at'] ?? '')
            );
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    private function item(string $title, string $subtitle, string $route, string $sinceId): WidgetItem {
        return new WidgetItem($title, $subtitle, $this->url->linkToRouteAbsolute($route), $this->iconUrl(), $sinceId);
    }

    private function iconUrl(): string {
        return $this->url->getAbsoluteURL($this->url->imagePath('reinhardterp', 'app.svg'));
    }

    private function todayHours(string $userId): float {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->sum('e.hours', 's'))->from('re_erp_workday_entries', 'e')
                ->innerJoin('e', 're_erp_workdays', 'w', $qb->expr()->eq('w.id', 'e.workday_id'))
                ->where($qb->expr()->eq('w.work_date', $qb->createNamedParameter(date('Y-m-d'))));
            if (!$this->permissions->isProjectSupervisor($userId)) {
                $qb->andWhere($qb->expr()->eq('w.user_id', $qb->createNamedParameter($userId)));
            }
            return (float)($qb->executeQuery()->fetchOne() ?: 0);
        } catch (\Throwable) {
            return 0.0;
        }
    }

    private function accessibleProjectIds(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('re_erp_projects')->where($qb->expr()->eq('is_archived', $qb->createNamedParameter(false, $qb::PARAM_BOOL)));
        $ids = array_map('intval', $qb->executeQuery()->fetchFirstColumn());
        return array_values(array_filter($ids, fn(int $id): bool => $this->permissions->canAccessProject($id, $userId)));
    }

    private function openReports(string $userId): int {
        try {
            $ids = $this->accessibleProjectIds($userId);
            if ($ids === []) return 0;
            $qb = $this->db->getQueryBuilder();
            $params = array_map(fn(int $id) => $qb->createNamedParameter($id), $ids);
            $qb->select($qb->func()->count('*', 'c'))->from('re_erp_reports')
                ->where($qb->expr()->in('project_id', $params))
                ->andWhere($qb->expr()->eq('locked', $qb->createNamedParameter(0)))
                ->andWhere($qb->expr()->eq('archived', $qb->createNamedParameter(0)));
            return (int)$qb->executeQuery()->fetchOne();
        } catch (\Throwable) {
            return 0;
        }
    }

    private function overdueProjects(string $userId): int {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('id')->from('re_erp_projects')
                ->where($qb->expr()->eq('is_archived', $qb->createNamedParameter(false, $qb::PARAM_BOOL)))
                ->andWhere($qb->expr()->isNotNull('due_date'))
                ->andWhere($qb->expr()->lt('due_date', $qb->createNamedParameter(date('Y-m-d'))));
            $count = 0;
            foreach ($qb->executeQuery()->fetchFirstColumn() as $id) {
                if ($this->permissions->canAccessProject((int)$id, $userId)) $count++;
            }
            return $count;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function unassignedDocuments(): int {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->count('*', 'c'))->from('re_erp_documents')
                ->where($qb->expr()->neq('processing_status', $qb->createNamedParameter('assigned')));
            return (int)$qb->executeQuery()->fetchOne();
        } catch (\Throwable) { return 0; }
    }

    private function lowStock(): int {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->func()->count('*', 'c'))->from('re_erp_materials')
                ->where($qb->expr()->gt('min_stock', $qb->createNamedParameter(0)))
                ->andWhere($qb->expr()->lte('stock_quantity', 'min_stock'));
            return (int)$qb->executeQuery()->fetchOne();
        } catch (\Throwable) { return 0; }
    }

    private function nextEvent(): ?array {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')->from('re_erp_team_events')
                ->where($qb->expr()->gte('start_at', $qb->createNamedParameter(date('Y-m-d H:i:s'))))
                ->andWhere($qb->expr()->eq('is_deleted', $qb->createNamedParameter(0)))
                ->orderBy('start_at', 'ASC')->setMaxResults(1);
            $row = $qb->executeQuery()->fetchAssociative();
            return $row ?: null;
        } catch (\Throwable) { return null; }
    }
}
