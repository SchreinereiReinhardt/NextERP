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
            $missing === [] ? count(self::TABLES).' Tabellen erreichbar' : 'Fehlend oder nicht lesbar: '.implode(', ', $missing),
            $missing === [] ? null : 'Nextcloud-App-Migrationen ausführen: sudo -u www-data php occ upgrade. Bleibt der Fehler bestehen, Datenbankrechte und Nextcloud-Log prüfen.'
        );

        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('sequence_key', 'current_value')->from('re_erp_sequences')->orderBy('sequence_key', 'ASC');
            $sequences = $qb->executeQuery()->fetchAllAssociative();
            $checks[] = $this->check('Nummernkreise', true, count($sequences).' aktive Zähler gefunden');
        } catch (\Throwable $e) {
            $checks[] = $this->check('Nummernkreise', false, $e->getMessage(), 'Zuerst die Datenbanktabellen prüfen und anschließend sudo -u www-data php occ upgrade ausführen.');
        }

        try {
            $this->folders->ensureFolderPath('ERP');
            $checks[] = $this->check('ERP-Dateiablage', true, 'ERP-Ordner ist erreichbar und beschreibbar');
        } catch (\Throwable $e) {
            $checks[] = $this->check('ERP-Dateiablage', false, $e->getMessage(), 'Nextcloud-Datenverzeichnis, Dateirechte und freien Speicher prüfen. Der Webserver-Benutzer muss auf die Nextcloud-Dateiablage zugreifen können.');
        }

        try {
            $logo = $this->folders->companyLogo();
            $checks[] = [
                'name' => 'Firmenlogo',
                'status' => $logo ? 'ok' : 'warning',
                'message' => $logo ? (string)$logo['path'] : 'Noch kein Logo hinterlegt',
                'recommendation' => $logo ? null : 'Unter Verwaltung → Einstellungen ein Firmenlogo hinterlegen. Es wird unter anderem für Belege und Rapporte verwendet.',
            ];
        } catch (\Throwable $e) {
            $checks[] = $this->check('Firmenlogo', false, $e->getMessage());
        }

        $phpOk = version_compare(PHP_VERSION, '8.2.0', '>=');
        $checks[] = $this->check('PHP-Version', $phpOk, PHP_VERSION.' (benötigt: >= 8.2)', $phpOk ? null : 'PHP auf mindestens 8.2 aktualisieren und sicherstellen, dass Webserver und CLI dieselbe unterstützte PHP-Version verwenden.');

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $checks[] = [
            'name'=>'HTTPS',
            'status'=>$https ? 'ok' : 'warning',
            'message'=>$https ? 'HTTPS erkannt' : 'HTTPS wurde für diese Anfrage nicht erkannt',
            'recommendation'=>$https ? null : 'HTTPS für die Nextcloud-Domain aktivieren. Bei Reverse Proxy außerdem X-Forwarded-Proto bzw. die Nextcloud-Proxy-Konfiguration prüfen.',
        ];

        $jsonLoaded = extension_loaded('json');
        $checks[] = $this->check('JSON', $jsonLoaded, $jsonLoaded ? 'geladen' : 'nicht geladen', $jsonLoaded ? null : 'Die PHP-JSON-Unterstützung aktivieren; sie ist für NextERP-API und Mobile erforderlich.');

        $memory = (string)ini_get('memory_limit');
        $memoryBytes = $this->iniBytes($memory);
        $memoryOk = $memory === '-1' || $memoryBytes >= 256 * 1024 * 1024;
        $checks[] = [
            'name'=>'PHP Memory Limit',
            'status'=>$memoryOk ? 'ok' : 'warning',
            'message'=>$memory !== '' ? $memory.' (empfohlen: mindestens 256M)' : 'nicht gesetzt',
            'recommendation'=>$memoryOk ? null : 'PHP memory_limit auf mindestens 256M setzen; bei großen PDFs oder vielen Dokumenten sind 512M sinnvoll.',
        ];


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
            $checks[] = ['name' => 'Nextcloud-Version', 'status' => 'warning', 'message' => 'Version konnte nicht automatisch ermittelt werden', 'recommendation' => 'Nextcloud-Version mit sudo -u www-data php occ status prüfen. NextERP 1.4.13 unterstützt laut App-Metadaten Nextcloud 33–34.'];
        } else {
            $ncOk = version_compare($ncVersion, '33.0.0', '>=') && version_compare($ncVersion, '35.0.0', '<');
            $checks[] = $this->check('Nextcloud-Version', $ncOk, $ncVersion.' (unterstützt: 33–34)', $ncOk ? null : 'Eine von NextERP unterstützte Nextcloud-Version (33 oder 34) verwenden. Vor einem Nextcloud-Upgrade zuerst NextERP-Kompatibilität prüfen.');
        }

        foreach (['mbstring', 'gd', 'curl', 'dom', 'xml', 'zip', 'openssl', 'iconv'] as $extension) {
            $loaded = extension_loaded($extension);
            $checks[] = $this->check(
                'PHP-Erweiterung '.$extension,
                $loaded,
                $loaded ? 'geladen' : 'nicht geladen',
                $loaded ? null : 'PHP-Erweiterung '.$extension.' für die vom Webserver verwendete PHP-Version installieren/aktivieren und PHP-FPM bzw. Apache anschließend neu laden.'
            );
        }

        $disabledFunctions = array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions'))));
        $procOpenAvailable = function_exists('proc_open') && !in_array('proc_open', $disabledFunctions, true);
        $checks[] = $this->check(
            'proc_open()',
            $procOpenAvailable,
            $procOpenAvailable ? 'verfügbar – externe PDF-Werkzeuge können gestartet werden' : 'nicht verfügbar oder in disable_functions gesperrt',
            $procOpenAvailable ? null : 'proc_open in der PHP-Konfiguration für Nextcloud freigeben, wenn PDF-Import/Vorschau genutzt werden soll. Danach PHP-FPM bzw. Apache neu laden.'
        );

        $pdftotext = $this->findExecutable('pdftotext');
        $checks[] = $this->check(
            'PDF-Texterkennung (pdftotext)',
            $pdftotext !== null,
            $pdftotext ?? 'nicht gefunden',
            $pdftotext !== null ? null : 'Unter Debian/Ubuntu installieren: apt install poppler-utils. Danach die Systemprüfung erneut ausführen.'
        );

        $pdftoppm = $this->findExecutable('pdftoppm');
        $checks[] = $this->check(
            'PDF-Vorschau (pdftoppm)',
            $pdftoppm !== null,
            $pdftoppm ?? 'nicht gefunden',
            $pdftoppm !== null ? null : 'Unter Debian/Ubuntu installieren: apt install poppler-utils. Das Paket stellt pdftoppm für PDF-Vorschauen bereit.'
        );

        $cronMode = $this->detectCronMode();
        $checks[] = [
            'name' => 'Nextcloud-Hintergrundjobs',
            'status' => $cronMode === 'cron' ? 'ok' : 'warning',
            'message' => $cronMode === 'cron'
                ? 'System-Cron ist konfiguriert'
                : ($cronMode === null ? 'Modus konnte nicht ermittelt werden' : 'Aktueller Modus: '.$cronMode),
            'recommendation' => $cronMode === 'cron' ? null : 'In Nextcloud unter Verwaltung → Grundeinstellungen „Cron“ wählen und cron.php systemseitig alle 5 Minuten als Webserver-Benutzer ausführen.',
        ];
        $erpEnabled = $this->permissions->isEnabled();
        $checks[] = $this->check('ERP-Zugriff', $erpEnabled, 'Rolle: '.$this->permissions->role(), $erpEnabled ? null : 'Dem Benutzer in NextERP eine passende Rolle bzw. Berechtigung zuweisen.');

        $checks[] = [
            'name' => 'Administrator-Notfallzugang',
            'status' => $this->permissions->isNextcloudAdmin() ? 'ok' : 'info',
            'message' => $this->permissions->isNextcloudAdmin()
                ? 'Dieses Nextcloud-Administratorkonto besitzt automatisch vollständigen NextERP-Adminzugriff'
                : 'Nextcloud-Administratoren besitzen unabhängig von der NextERP-Rollentabelle vollständigen NextERP-Adminzugriff',
            'recommendation' => null,
        ];
        $checks[] = [
            'name' => 'Berechtigungsmodell',
            'status' => 'ok',
            'message' => 'Nextcloud-Administratoren haben immer Adminzugriff; andere nicht konfigurierte Benutzer erhalten standardmäßig keinen NextERP-Zugriff',
            'recommendation' => null,
        ];
        $checks[] = [
            'name' => 'Mobile Projektordner',
            'status' => 'ok',
            'message' => 'Mobile Dokumentzugriffe und Uploads beachten Projekt- und Ordnerfreigaben',
            'recommendation' => null,
        ];
        $checks[] = [
            'name' => 'Mobile Upload-Dateitypen',
            'status' => 'ok',
            'message' => 'Uploads sind auf freigegebene Dokument- und Bildformate begrenzt',
            'recommendation' => null,
        ];
        $checks[] = [
            'name' => 'Release-Metadaten',
            'status' => 'ok',
            'message' => 'Lizenz, Repository, Support-/Fehlerlink und unterstützte Nextcloud-Versionen sind in den App-Metadaten hinterlegt',
            'recommendation' => null,
        ];
        $migrationDir = dirname(__DIR__).'/Migration';
        $migrationFiles = is_dir($migrationDir) ? glob($migrationDir.'/Version*.php') : [];
        $checks[] = [
            'name' => 'Datenbank-Migrationen',
            'status' => !empty($migrationFiles) ? 'ok' : 'warning',
            'message' => !empty($migrationFiles)
                ? count($migrationFiles).' versionierte NextERP-Migrationen im Release vorhanden'
                : 'Keine versionierten NextERP-Migrationen gefunden',
            'recommendation' => !empty($migrationFiles) ? null : 'Release-Paket prüfen. Für bestehende Installationen müssen notwendige Schemaänderungen als Nextcloud-Migrationen ausgeliefert werden.',
        ];
        $checks[] = [
            'name' => 'Backup vor Updates',
            'status' => 'info',
            'message' => 'NextERP verwendet die Nextcloud-Datenbank, App-Konfiguration und Nextcloud-Dateispeicher; diese Bereiche müssen gemeinsam gesichert werden',
            'recommendation' => 'Vor Updates ein vollständiges Nextcloud-Backup inklusive Datenbank, config-Verzeichnis und Datenverzeichnis erstellen und die Wiederherstellung regelmäßig auf einer Testinstanz prüfen.',
        ];
        $checks[] = [
            'name' => 'Deinstallation',
            'status' => 'info',
            'message' => 'App-Deaktivierung, App-Entfernung und bewusste Löschung von Geschäftsdaten sind getrennte Vorgänge',
            'recommendation' => 'Vor dem Entfernen der App Rapporte/Projektunterlagen sichern und ein vollständiges Backup erstellen. Geschäftsdaten niemals durch manuelles Löschen von Datenbanktabellen oder Projektordnern entfernen.',
        ];


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

    private function iniBytes(string $value): int {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return $value === '-1' ? PHP_INT_MAX : 0;
        }
        $unit = strtolower(substr($value, -1));
        $number = (float)$value;
        return match ($unit) {
            'g' => (int)($number * 1024 * 1024 * 1024),
            'm' => (int)($number * 1024 * 1024),
            'k' => (int)($number * 1024),
            default => (int)$number,
        };
    }

    private function check(string $name, bool $ok, string $message, ?string $recommendation = null): array {
        return [
            'name' => $name,
            'status' => $ok ? 'ok' : 'error',
            'message' => $message,
            'recommendation' => $ok ? null : $recommendation,
        ];
    }
}
