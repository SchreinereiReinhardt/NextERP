<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;

final class SystemCheckService {
    private const TABLES = [
        're_erp_activities',
        're_erp_billing_batch_items',
        're_erp_billing_batches',
        're_erp_communications',
        're_erp_customer_contacts',
        're_erp_customer_reminders',
        're_erp_customers',
        're_erp_document_rules',
        're_erp_documents',
        're_erp_hourly_rates',
        're_erp_invoice_items',
        're_erp_invoices',
        're_erp_material_groups',
        're_erp_materials',
        're_erp_mobile_tokens',
        're_erp_offer_items',
        're_erp_offers',
        're_erp_order_items',
        're_erp_orders',
        're_erp_project_documents',
        're_erp_project_users',
        're_erp_projects',
        're_erp_report_files',
        're_erp_report_hours',
        're_erp_report_items',
        're_erp_reports',
        're_erp_sequences',
        're_erp_stock_movements',
        're_erp_suppliers',
        're_erp_team_events',
        're_erp_time_timers',
        're_erp_user_roles',
        're_erp_workday_entries',
        're_erp_workday_materials',
        're_erp_workdays',
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

        $checks[] = $this->check('PHP-Version', version_compare(PHP_VERSION, '8.2.0', '>='), PHP_VERSION.' (benötigt: >= 8.2)');
        $checks[] = $this->check('HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https', 'Für Mobile/PWA und sichere Anmeldung wird HTTPS empfohlen');
        $checks[] = $this->check('JSON', extension_loaded('json'), extension_loaded('json') ? 'geladen' : 'nicht geladen');
        $memory = (string)ini_get('memory_limit');
        $checks[] = ['name'=>'PHP Memory Limit','status'=>'ok','message'=>$memory !== '' ? $memory : 'nicht gesetzt'];


        $ncVersion = 'unbekannt';
        try {
            if (class_exists('\OC_Util') && method_exists('\OC_Util', 'getVersion')) {
                $v = \OC_Util::getVersion();
                if (is_array($v) && $v !== []) {
                    $ncVersion = implode('.', array_map('strval', $v));
                }
            }
        } catch (\Throwable) {
        }
        if ($ncVersion === 'unbekannt') {
            $checks[] = ['name' => 'Nextcloud-Version', 'status' => 'warning', 'message' => 'Version konnte nicht automatisch ermittelt werden'];
        } else {
            $ncOk = version_compare($ncVersion, '33.0.0', '>=') && version_compare($ncVersion, '35.0.0', '<');
            $checks[] = $this->check('Nextcloud-Version', $ncOk, $ncVersion.' (unterstützt: 33–34)');
        }

        foreach (['mbstring', 'gd', 'curl', 'dom', 'xml', 'zip', 'openssl', 'iconv'] as $extension) {
            $checks[] = $this->check('PHP-Erweiterung '.$extension, extension_loaded($extension), extension_loaded($extension) ? 'geladen' : 'nicht geladen');
        }

        $disabledFunctions = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        $procOpenAvailable = function_exists('proc_open') && !in_array('proc_open', $disabledFunctions, true);
        $checks[] = $this->check(
            'proc_open()',
            $procOpenAvailable,
            $procOpenAvailable ? 'verfügbar – externe PDF-Werkzeuge können gestartet werden' : 'nicht verfügbar oder in disable_functions gesperrt'
        );

        $pdftotext = $this->findExecutable('pdftotext');
        $checks[] = $this->check(
            'PDF-Texterkennung (pdftotext)',
            $pdftotext !== null,
            $pdftotext ?? 'nicht gefunden – unter Debian/Ubuntu: apt install poppler-utils'
        );

        $pdftoppm = $this->findExecutable('pdftoppm');
        $checks[] = $this->check(
            'PDF-Vorschau (pdftoppm)',
            $pdftoppm !== null,
            $pdftoppm ?? 'nicht gefunden – unter Debian/Ubuntu: apt install poppler-utils'
        );

        $cronMode = $this->detectCronMode();
        $checks[] = [
            'name' => 'Nextcloud-Hintergrundjobs',
            'status' => $cronMode === 'cron' ? 'ok' : 'warning',
            'message' => $cronMode === 'cron'
                ? 'System-Cron ist konfiguriert'
                : ($cronMode === null ? 'Modus konnte nicht ermittelt werden – System-Cron alle 5 Minuten empfohlen' : 'Aktueller Modus: '.$cronMode.' – System-Cron alle 5 Minuten empfohlen'),
        ];
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

    private function findExecutable(string $name): ?string {
        if (!function_exists('proc_open')) {
            return null;
        }
        $disabled = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        if (in_array('proc_open', $disabled, true)) {
            return null;
        }
        $paths = ['/usr/bin/'.$name, '/usr/local/bin/'.$name, '/bin/'.$name];
        foreach ($paths as $path) {
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }
        return null;
    }

    private function detectCronMode(): ?string {
        try {
            if (class_exists('\\OC')) {
                $config = \OC::$server->get(\OCP\IConfig::class);
                $mode = (string)$config->getAppValue('core', 'backgroundjobs_mode', '');
                return $mode !== '' ? $mode : null;
            }
        } catch (\Throwable) {
        }
        return null;
    }

    private function check(string $name, bool $ok, string $message): array {
        return ['name' => $name, 'status' => $ok ? 'ok' : 'error', 'message' => $message];
    }
}
