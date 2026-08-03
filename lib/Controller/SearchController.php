<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;

final class SearchController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private IDBConnection $db,
        private IURLGenerator $url,
        private PermissionService $permissions,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function index(string $q = ''): JSONResponse {
        $query = trim($q);
        if (mb_strlen($query) < 2) {
            return new JSONResponse(['results' => []]);
        }

        $results = [];
        $like = '%' . $this->db->escapeLikeParameter($query) . '%';

        if ($this->permissions->can('customers')) {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('id', 'customer_no', 'name', 'city')
                ->from('re_erp_customers')
                ->where($qb->expr()->orX(
                    $qb->expr()->iLike('name', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('customer_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('email', $qb->createNamedParameter($like))
                ))
                ->setMaxResults(6)
                ->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $results[] = [
                    'type' => 'Kunde',
                    'icon' => '👥',
                    'title' => trim(($row['customer_no'] ?? '') . ' ' . ($row['name'] ?? '')),
                    'subtitle' => (string)($row['city'] ?? ''),
                    'url' => $this->url->linkToRoute('reinhardterp.page.customerDetail', ['id' => (int)$row['id']]),
                ];
            }
        }

        if ($this->permissions->can('projects')) {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('p.id', 'p.project_no', 'p.title', 'p.status', 'c.name AS customer_name')
                ->from('re_erp_projects', 'p')
                ->leftJoin('p', 're_erp_customers', 'c', $qb->expr()->eq('c.id', 'p.customer_id'))
                ->where($qb->expr()->orX(
                    $qb->expr()->iLike('p.title', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('p.project_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('c.name', $qb->createNamedParameter($like))
                ))
                ->setMaxResults(6)
                ->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $results[] = [
                    'type' => 'Projekt',
                    'icon' => '📁',
                    'title' => trim(($row['project_no'] ?? '') . ' ' . ($row['title'] ?? '')),
                    'subtitle' => trim(($row['customer_name'] ?? '') . ' · ' . ($row['status'] ?? '')),
                    'url' => $this->url->linkToRoute('reinhardterp.page.projectDetail', ['id' => (int)$row['id']]),
                ];
            }
        }

        if ($this->permissions->can('reports')) {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('r.id', 'r.report_no', 'r.title', 'r.status', 'p.project_no')
                ->from('re_erp_reports', 'r')
                ->leftJoin('r', 're_erp_projects', 'p', $qb->expr()->eq('p.id', 'r.project_id'))
                ->where($qb->expr()->orX(
                    $qb->expr()->iLike('r.report_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('r.title', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('p.project_no', $qb->createNamedParameter($like))
                ))
                ->setMaxResults(5)
                ->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $results[] = [
                    'type' => 'Rapport',
                    'icon' => '📝',
                    'title' => trim(($row['report_no'] ?? '') . ' ' . ($row['title'] ?? '')),
                    'subtitle' => trim(($row['project_no'] ?? '') . ' · ' . ($row['status'] ?? '')),
                    'url' => $this->url->linkToRoute('reinhardterp.module.reportDetail', ['id' => (int)$row['id']]),
                ];
            }
        }

        return new JSONResponse(['results' => array_slice($results, 0, 15)]);
    }
}
