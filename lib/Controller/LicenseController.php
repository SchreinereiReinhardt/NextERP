<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Service\EditionService;
use OCA\ReinhardtERP\Service\LicenseService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
final class LicenseController extends Controller{
 public function __construct(string $appName,IRequest $request,private PermissionService $permissions,private EditionService $edition,private LicenseService $licenses,private IURLGenerator $url){parent::__construct($appName,$request);}
 #[NoAdminRequired]
 #[NoCSRFRequired]
 public function index():TemplateResponse{$this->permissions->assert('settings');return new TemplateResponse($this->appName,'license_pro',['status'=>$this->edition->status(),'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error',''),'urlGenerator'=>$this->url]);}
 #[NoAdminRequired] public function activate(string $licenseKey=''):RedirectResponse{$this->permissions->assert('settings');try{$this->licenses->activate($licenseKey);return $this->go('message','Betrio Pro Lizenz wurde bestätigt.');}catch(\Throwable $e){return $this->go('error',$e->getMessage());}}
 private function go(string $key,string $value):RedirectResponse{return new RedirectResponse($this->url->linkToRoute('reinhardterp.license.index').'?'.$key.'='.rawurlencode($value));}
}
