<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;

final class DocumentBackgroundScanService {
    public function __construct(
        private IRootFolder $rootFolder,
        private IConfig $config,
        private IDBConnection $db,
        private DocumentClassifierService $classifier,
        private DocumentRuleService $rules,
    ) {}

    public function scan(): int {
        $uid = trim($this->config->getAppValue('reinhardterp', 'document_inbox_owner', ''));
        if ($uid === '') {
            return 0;
        }
        try {
            $userRoot = $this->rootFolder->getUserFolder($uid);
            $inbox = $this->folderPath($userRoot, 'ERP/00_Dokumenteneingang/01_Unbearbeitet');
        } catch (\Throwable) {
            return 0;
        }

        $added = 0;
        foreach ($inbox->getDirectoryListing() as $node) {
            if (!$node instanceof File || $this->pathExists('ERP/00_Dokumenteneingang/01_Unbearbeitet/'.$node->getName())) {
                continue;
            }
            try {
                $checksum = hash('sha256', $node->getContent());
                $suggestion = $this->classifier->classify(
                    $node->getName(),
                    $this->rows('re_erp_customers', 'name'),
                    $this->rows('re_erp_projects', 'project_no'),
                    $this->rows('re_erp_suppliers', 'name'),
                );
                $suggestion = array_replace($suggestion, $this->rules->apply($node->getName()));
                $now = date('Y-m-d H:i:s');
                $qb = $this->db->getQueryBuilder();
                $qb->insert('re_erp_documents')->values([
                    'file_id'=>$qb->createNamedParameter($node->getId()),
                    'file_name'=>$qb->createNamedParameter($node->getName()),
                    'original_name'=>$qb->createNamedParameter($node->getName()),
                    'file_path'=>$qb->createNamedParameter('ERP/00_Dokumenteneingang/01_Unbearbeitet/'.$node->getName()),
                    'mime_type'=>$qb->createNamedParameter($node->getMimeType()),
                    'file_size'=>$qb->createNamedParameter($node->getSize()),
                    'checksum'=>$qb->createNamedParameter($checksum),
                    'source'=>$qb->createNamedParameter('background_scan'),
                    'document_type'=>$qb->createNamedParameter('unassigned'),
                    'status'=>$qb->createNamedParameter('unassigned'),
                    'processing_status'=>$qb->createNamedParameter('new'),
                    'currency'=>$qb->createNamedParameter('EUR'),
                    'suggested_type'=>$qb->createNamedParameter($suggestion['suggested_type'] ?? 'unassigned'),
                    'suggested_document_no'=>$qb->createNamedParameter($suggestion['suggested_document_no'] ?? null),
                    'suggested_document_date'=>$qb->createNamedParameter($suggestion['suggested_document_date'] ?? null),
                    'suggested_customer_id'=>$qb->createNamedParameter($suggestion['suggested_customer_id'] ?? null),
                    'suggested_project_id'=>$qb->createNamedParameter($suggestion['suggested_project_id'] ?? null),
                    'suggested_supplier_id'=>$qb->createNamedParameter($suggestion['suggested_supplier_id'] ?? null),
                    'suggestion_confidence'=>$qb->createNamedParameter((int)($suggestion['suggestion_confidence'] ?? 0)),
                    'suggestion_reason'=>$qb->createNamedParameter($suggestion['suggestion_reason'] ?? null),
                    'auto_rule_id'=>$qb->createNamedParameter($suggestion['auto_rule_id'] ?? null),
                    'analyzed_at'=>$qb->createNamedParameter($now),
                    'detected_at'=>$qb->createNamedParameter($now),
                    'last_seen_at'=>$qb->createNamedParameter($now),
                    'created_by'=>$qb->createNamedParameter('system'),
                    'created_at'=>$qb->createNamedParameter($now),
                    'updated_at'=>$qb->createNamedParameter($now),
                ])->executeStatement();
                $added++;
            } catch (\Throwable) {
                continue;
            }
        }
        $this->config->setAppValue('reinhardterp', 'document_last_scan_at', date('Y-m-d H:i:s'));
        $this->config->setAppValue('reinhardterp', 'document_last_scan_count', (string)$added);
        return $added;
    }

    private function folderPath(Folder $root, string $path): Folder {
        $node=$root;
        foreach (explode('/', trim($path,'/')) as $part) {
            try { $next=$node->get($part); }
            catch (\OCP\Files\NotFoundException) { $next=$node->newFolder($part); }
            if (!$next instanceof Folder) { throw new \RuntimeException('Ungültiger Dokumentenpfad.'); }
            $node=$next;
        }
        return $node;
    }
    private function pathExists(string $path): bool {
        $qb=$this->db->getQueryBuilder();
        $qb->select('id')->from('re_erp_documents')->where($qb->expr()->eq('file_path',$qb->createNamedParameter($path)))->setMaxResults(1);
        return $qb->executeQuery()->fetchOne()!==false;
    }
    private function rows(string $table,string $order): array {
        $qb=$this->db->getQueryBuilder();$qb->select('*')->from($table)->orderBy($order,'ASC');return $qb->executeQuery()->fetchAllAssociative();
    }
}
