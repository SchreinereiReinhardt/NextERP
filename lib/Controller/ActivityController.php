<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\ActivityService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;

final class ActivityController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private ActivityService $activities,
        private PermissionService $permissions,
        private IURLGenerator $url,
    ) { parent::__construct($appName, $request); }

    #[NoAdminRequired]
    public function addProjectNote(int $projectId, int $customerId, string $note): RedirectResponse {
        $this->permissions->assertProjectAccess($projectId);
        $note = trim($note);
        if ($note === '') {
            throw new \InvalidArgumentException('Bitte eine Notiz eingeben.');
        }
        $this->activities->record('project', $projectId, 'note', 'Notiz hinzugefügt', $note, $customerId, $projectId);
        return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projectDetail', ['id' => $projectId]));
    }
}
