<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\SystemCheckService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
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
}
