<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;

final class SystemCheckService {
    private const TABLES = [
        're_erp_customers', 're_erp_projects', 're_erp_reports',
        're_erp_report_hours', 're_erp_report_items', 're_erp_report_files',
        're_erp_workdays', 're_erp_workday_entries', 're_erp_materials',
        're_erp_sequences', 're_erp_user_roles', 're_erp_hourly_rates',
        're_erp_activities', 're_erp_team_events', 're_erp_time_timers',
        're_erp_billing_batches', 're_erp_billing_batch_items',
    ];

    public function __construct(
        private IDBConnection $db,
        private FolderService $folders,
        private PermissionService $permissions,
    ) {}

    public function run(): array {
        $checks = [];
        $missing = [];
        foreach (self::TABLES as $table) {
            try {
                $qb = $this->db->getQueryBuilder();
                $qb->select($qb->func()->count('*', 'c'))->from($table);
                $qb->executeQuery()->fetchOne();
            } catch (\Throwable) {
                $missing[] = $table;
            }
        }
        $checks[] = $this->check(
            'Datenbanktabellen',
            $missing === [],
            $missing === [] ? count(self::TABLES).' Tabellen erreichbar' : 'Fehlend oder nicht lesbar: '.implode(', ', $missing)
        );

        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('sequence_key', 'current_value')->from('re_erp_sequences')->orderBy('sequence_key', 'ASC');
            $sequences = $qb->executeQuery()->fetchAllAssociative();
            $checks[] = $this->check('Nummernkreise', true, count($sequences).' aktive Zähler gefunden');
        } catch (\Throwable $e) {
            $checks[] = $this->check('Nummernkreise', false, $e->getMessage());
        }

        try {
            $this->folders->ensureFolderPath('ERP');
            $checks[] = $this->check('ERP-Dateiablage', true, 'ERP-Ordner ist erreichbar und beschreibbar');
        } catch (\Throwable $e) {
            $checks[] = $this->check('ERP-Dateiablage', false, $e->getMessage());
        }

        try {
            $logo = $this->folders->companyLogo();
            $checks[] = [
                'name' => 'Firmenlogo',
                'status' => $logo ? 'ok' : 'warning',
                'message' => $logo ? (string)$logo['path'] : 'Noch kein Logo hinterlegt',
            ];
        } catch (\Throwable $e) {
            $checks[] = $this->check('Firmenlogo', false, $e->getMessage());
        }

        $checks[] = $this->check('PHP-Version', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION);
        $checks[] = $this->check('ERP-Zugriff', $this->permissions->isEnabled(), 'Rolle: '.$this->permissions->role());

        $failed = count(array_filter($checks, static fn(array $c): bool => $c['status'] === 'error'));
        $warnings = count(array_filter($checks, static fn(array $c): bool => $c['status'] === 'warning'));
        return [
            'checks' => $checks,
            'failed' => $failed,
            'warnings' => $warnings,
            'healthy' => $failed === 0,
            'checkedAt' => date('Y-m-d H:i:s'),
        ];
    }

    private function check(string $name, bool $ok, string $message): array {
        return ['name' => $name, 'status' => $ok ? 'ok' : 'error', 'message' => $message];
    }
}
