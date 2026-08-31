<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\MobileService;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use OCP\IUserSession;

final class MobileOcsController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private MobileService $mobile,
        private IUserSession $userSession,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * Authenticated mobile login via Nextcloud's OCS authentication layer.
     * HTTP Basic credentials are validated by Nextcloud core, including app passwords.
     */
    #[NoAdminRequired, NoCSRFRequired]
    public function sessionLogin(string $deviceName = 'Betrio Android'): DataResponse {
        $user = $this->userSession->getUser();
        if ($user === null) {
            return new DataResponse(['success' => false, 'message' => 'Nextcloud-Anmeldung fehlgeschlagen.'], 401);
        }

        try {
            return new DataResponse($this->mobile->loginUser($user, $deviceName !== '' ? $deviceName : null));
        } catch (\Throwable $e) {
            $message = $e->getMessage() !== '' ? $e->getMessage() : 'Anmeldung fehlgeschlagen.';
            $lower = strtolower($message);
            $status = str_contains($lower, 'freigeschaltet') || str_contains($lower, 'gesperrt') || str_contains($lower, 'berechtigung') ? 403 : 401;
            return new DataResponse(['success' => false, 'message' => $message], $status);
        }
    }
}
