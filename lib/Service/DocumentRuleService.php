<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;

final class DocumentRuleService {
    public function __construct(private IDBConnection $db) {}

    public function all(): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('re_erp_document_rules')->orderBy('priority', 'ASC')->addOrderBy('id', 'ASC');
        return $qb->executeQuery()->fetchAllAssociative();
    }

    public function create(array $data): int {
        $name = trim((string)($data['name'] ?? ''));
        $value = trim((string)($data['match_value'] ?? ''));
        if ($name === '' || $value === '') {
            throw new \InvalidArgumentException('Regelname und Suchwert sind erforderlich.');
        }
        $qb = $this->db->getQueryBuilder();
        $qb->insert('re_erp_document_rules')->values([
            'name' => $qb->createNamedParameter($name),
            'enabled' => $qb->createNamedParameter(1),
            'priority' => $qb->createNamedParameter(max(1, (int)($data['priority'] ?? 100))),
            'match_field' => $qb->createNamedParameter('filename'),
            'match_operator' => $qb->createNamedParameter('contains'),
            'match_value' => $qb->createNamedParameter($value),
            'document_type' => $qb->createNamedParameter($this->nullableString($data['document_type'] ?? null)),
            'customer_id' => $qb->createNamedParameter($this->nullableInt($data['customer_id'] ?? null)),
            'project_id' => $qb->createNamedParameter($this->nullableInt($data['project_id'] ?? null)),
            'supplier_id' => $qb->createNamedParameter($this->nullableInt($data['supplier_id'] ?? null)),
            'created_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
            'updated_at' => $qb->createNamedParameter(date('Y-m-d H:i:s')),
        ])->executeStatement();
        return (int)$this->db->lastInsertId('re_erp_document_rules');
    }

    public function delete(int $id): void {
        $qb = $this->db->getQueryBuilder();
        $qb->delete('re_erp_document_rules')->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))->executeStatement();
    }

    public function apply(string $filename): array {
        $normalised = $this->normalise($filename);
        foreach ($this->all() as $rule) {
            if (!(bool)$rule['enabled']) {
                continue;
            }
            $needle = $this->normalise((string)$rule['match_value']);
            if ($needle === '' || !str_contains($normalised, $needle)) {
                continue;
            }
            return [
                'auto_rule_id' => (int)$rule['id'],
                'suggested_type' => $rule['document_type'] ?: 'unassigned',
                'suggested_customer_id' => $rule['customer_id'] ? (int)$rule['customer_id'] : null,
                'suggested_project_id' => $rule['project_id'] ? (int)$rule['project_id'] : null,
                'suggested_supplier_id' => $rule['supplier_id'] ? (int)$rule['supplier_id'] : null,
                'suggestion_confidence' => 100,
            ];
        }
        return [];
    }

    private function nullableInt(mixed $value): ?int { $id=(int)$value; return $id>0?$id:null; }
    private function nullableString(mixed $value): ?string { $v=trim((string)$value); return $v!==''?$v:null; }
    private function normalise(string $value): string {
        $value = strtr($value, ['Ä'=>'Ae','Ö'=>'Oe','Ü'=>'Ue','ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss','+'=>' ']);
        return trim((string)preg_replace('/[^a-z0-9]+/u', ' ', strtolower($value)));
    }
}
