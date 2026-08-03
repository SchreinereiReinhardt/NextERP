<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\MobileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

final class MobileController extends Controller {
 public function __construct(string $appName,IRequest $request,private MobileService $mobile){parent::__construct($appName,$request);}
 #[PublicPage,NoCSRFRequired] public function login(string $username='',string $password='',string $deviceName=''):JSONResponse{return $this->run(function()use($username,$password,$deviceName){$body=$this->jsonBody();return $this->mobile->login((string)($body['username']??$username),(string)($body['password']??$password),(string)($body['deviceName']??$deviceName)?:null);});}
 #[PublicPage,NoCSRFRequired] public function refresh(string $refreshToken=''):JSONResponse{return $this->run(function()use($refreshToken){$body=$this->jsonBody();return $this->mobile->refresh((string)($body['refreshToken']??$refreshToken));});}
 #[PublicPage,NoCSRFRequired] public function logout():JSONResponse{return $this->authRun(function(array $auth){$this->mobile->logout((int)$auth['tokenId']);return ['loggedOut'=>true];});}
 #[PublicPage,NoCSRFRequired] public function bootstrap():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->bootstrap((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function dashboard():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->dashboard((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function projects(int $limit=100):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projects((string)$a['uid'],$limit));}
 #[PublicPage,NoCSRFRequired] public function project(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->project((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function projectDocuments(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectDocuments((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function projectPhotos(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectPhotos((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function material(string $q=''):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->materials($q));}
 #[PublicPage,NoCSRFRequired] public function report():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createReport((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function time():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createTime((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function upload(int $projectId=0,string $type='document'):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->upload((string)$a['uid'],$this->request->getUploadedFile('file')??[],$projectId,$type));}
 #[PublicPage,NoCSRFRequired] public function scan(int $projectId=0):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->upload((string)$a['uid'],$this->request->getUploadedFile('file')??[],$projectId,'scan'));}
 #[PublicPage,NoCSRFRequired] public function sync():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->sync((string)$a['uid'],(array)($this->jsonBody()['changes']??[])));}
 private function authRun(callable $fn):JSONResponse{return $this->run(function()use($fn){$auth=$this->mobile->authenticate((string)$this->request->getHeader('Authorization'));return $fn($auth);});}
 private function jsonBody(): array {
  // IRequest::getParams() is the public Nextcloud API and includes decoded JSON body parameters.
  $params = $this->request->getParams();
  return is_array($params) ? $params : [];
 }
 private function run(callable $fn):JSONResponse{try{return new JSONResponse(['success'=>true,'data'=>$fn(),'errors'=>[],'message'=>'']);}catch(\InvalidArgumentException $e){return new JSONResponse(['success'=>false,'data'=>null,'errors'=>[$e->getMessage()],'message'=>$e->getMessage(),'code'=>'VALIDATION_ERROR'],400);}catch(\Throwable $e){$message=$e->getMessage()!==''?$e->getMessage():'Unbekannter Fehler.';$status=str_contains(strtolower($message),'token')||str_contains(strtolower($message),'anmeldung')?401:500;return new JSONResponse(['success'=>false,'data'=>null,'errors'=>[$message],'message'=>$message,'code'=>$status===401?'AUTHENTICATION_FAILED':'MOBILE_API_ERROR'],$status);}}
}
