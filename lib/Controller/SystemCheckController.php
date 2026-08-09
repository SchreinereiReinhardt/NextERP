<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\SystemCheckService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IRequest;

final class SystemCheckController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private SystemCheckService $checks,
        private PermissionService $permissions,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function index(): TemplateResponse {
        $this->permissions->assert('settings');
        return new TemplateResponse($this->appName, 'system_check', $this->checks->run());
    }

    #[NoAdminRequired, NoCSRFRequired]
    public function diagnostics(): DataDownloadResponse {
        $this->permissions->assert('settings');
        $result = $this->checks->run();
        $lines = [
            'NextERP Diagnosebericht',
            '======================',
            'Erstellt: '.date('Y-m-d H:i:s'),
            'NextERP: 1.4.13',
            'PHP: '.PHP_VERSION,
            'PHP SAPI: '.PHP_SAPI,
            'Betriebssystem: '.PHP_OS_FAMILY,
            '',
            'Systemprüfung',
            '-------------',
        ];
        foreach ($result['checks'] as $check) {
            $lines[] = '['.strtoupper((string)$check['status']).'] '.(string)$check['name'].': '.(string)$check['message'];
            if ($check['status'] !== 'ok' && !empty($check['recommendation'])) {
                $lines[] = '  Lösung: '.(string)$check['recommendation'];
            }
        }
        $lines[] = '';
        $lines[] = 'Zusammenfassung: '.(string)$result['failed'].' Fehler, '.(string)$result['warnings'].' Hinweise';
        $lines[] = '';
        $lines[] = 'Datenschutz: Dieser Bericht enthält bewusst keine Kundendaten, Projektdaten, Passwörter, Tokens, Dateiinhalte oder vollständigen Serverpfade.';
        $content = implode("\n", $lines)."\n";
        return new DataDownloadResponse($content, 'NextERP-Diagnose-'.date('Ymd-His').'.txt', 'text/plain; charset=utf-8');
    }
}
