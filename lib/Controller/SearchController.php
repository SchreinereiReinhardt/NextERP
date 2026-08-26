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
                if (!$this->permissions->canAccessProject((int)$row['id'])) { continue; }
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
            $rows = $qb->select('r.id', 'r.project_id', 'r.report_no', 'r.title', 'r.status', 'p.project_no')
                ->from('re_erp_reports', 'r')
                ->leftJoin('r', 're_erp_projects', 'p', $qb->expr()->eq('p.id', 'r.project_id'))
                ->where($qb->expr()->orX(
                    $qb->expr()->iLike('r.report_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('r.title', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('p.project_no', $qb->createNamedParameter($like))
                ))
                ->andWhere($qb->expr()->eq('r.archived', $qb->createNamedParameter(0)))
                ->setMaxResults(5)
                ->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                if (!$this->permissions->canAccessProject((int)$row['project_id'])) { continue; }
                $results[] = [
                    'type' => 'Rapport',
                    'icon' => '📝',
                    'title' => trim(($row['report_no'] ?? '') . ' ' . ($row['title'] ?? '')),
                    'subtitle' => trim(($row['project_no'] ?? '') . ' · ' . ($row['status'] ?? '')),
                    'url' => $this->url->linkToRoute('reinhardterp.module.reportDetail', ['id' => (int)$row['id']]),
                ];
            }
        }

        if ($this->permissions->can('documents')) {
            $qb = $this->db->getQueryBuilder();
            $rows = $qb->select('d.id', 'd.original_name', 'd.document_no', 'd.suggested_document_no', 'd.document_type', 'd.processing_status', 'c.name AS customer_name', 'p.project_no', 's.name AS supplier_name')
                ->from('re_erp_documents', 'd')
                ->leftJoin('d', 're_erp_customers', 'c', $qb->expr()->eq('c.id', 'd.customer_id'))
                ->leftJoin('d', 're_erp_projects', 'p', $qb->expr()->eq('p.id', 'd.project_id'))
                ->leftJoin('d', 're_erp_suppliers', 's', $qb->expr()->eq('s.id', 'd.supplier_id'))
                ->where($qb->expr()->orX(
                    $qb->expr()->iLike('d.original_name', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('d.file_name', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('d.document_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('d.suggested_document_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('c.name', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('p.project_no', $qb->createNamedParameter($like)),
                    $qb->expr()->iLike('s.name', $qb->createNamedParameter($like))
                ))
                ->orderBy('d.created_at', 'DESC')
                ->setMaxResults(6)
                ->executeQuery()->fetchAllAssociative();
            foreach ($rows as $row) {
                $number = (string)($row['document_no'] ?: ($row['suggested_document_no'] ?? ''));
                $context = array_filter([(string)($row['customer_name'] ?? ''), (string)($row['project_no'] ?? ''), (string)($row['supplier_name'] ?? '')]);
                $results[] = [
                    'type' => 'Beleg',
                    'icon' => '📄',
                    'title' => trim(($number !== '' ? $number . ' · ' : '') . (string)$row['original_name']),
                    'subtitle' => implode(' · ', $context),
                    'url' => $this->url->linkToRoute('reinhardterp.document.review', ['id' => (int)$row['id']]),
                ];
            }
        }

        return new JSONResponse(['results' => array_slice($results, 0, 15)]);
    }
}
