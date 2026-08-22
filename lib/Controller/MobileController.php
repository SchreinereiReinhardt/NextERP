<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;

use OCA\ReinhardtERP\Service\MobileService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\IRequest;

final class MobileController extends Controller {
 public function __construct(string $appName,IRequest $request,private MobileService $mobile){parent::__construct($appName,$request);}
 #[PublicPage,NoCSRFRequired] public function login(string $username='',string $password='',string $deviceName=''):JSONResponse{return $this->run(function()use($username,$password,$deviceName){$body=$this->jsonBody();return $this->mobile->login((string)($body['username']??$username),(string)($body['password']??$password),(string)($body['deviceName']??$deviceName)?:null);});}
 #[PublicPage,NoCSRFRequired] public function refresh(string $refreshToken=''):JSONResponse{return $this->run(function()use($refreshToken){$body=$this->jsonBody();return $this->mobile->refresh((string)($body['refreshToken']??$refreshToken));});}
 #[PublicPage,NoCSRFRequired] public function logout():JSONResponse{return $this->authRun(function(array $auth){$this->mobile->logout((int)$auth['tokenId']);return ['loggedOut'=>true];});}
 #[PublicPage,NoCSRFRequired] public function bootstrap():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->bootstrap((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function dashboard():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->dashboard((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function openReports():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->openReports((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function customers():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->customers((string)$a['uid']));}
 #[PublicPage,NoCSRFRequired] public function createCustomer():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createCustomer((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function updateCustomer(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->updateCustomer((string)$a['uid'],$id,$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function projects(int $limit=100):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projects((string)$a['uid'],$limit));}
 #[PublicPage,NoCSRFRequired] public function createProject():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createProject((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function updateProject(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->updateProject((string)$a['uid'],$id,$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function project(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->project((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function projectDocuments(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectDocuments((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function createProjectNote(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createProjectNote((string)$a['uid'],$id,$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function projectNotes(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectNotes((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function updateProjectNote(int $projectId,int $noteId):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->updateProjectNote((string)$a['uid'],$projectId,$noteId,$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function deleteProjectNote(int $projectId,int $noteId):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->deleteProjectNote((string)$a['uid'],$projectId,$noteId));}
 #[PublicPage,NoCSRFRequired] public function projectDocumentContent(int $id):DataDisplayResponse|JSONResponse{
  try{
   $auth=$this->mobile->authenticate((string)$this->request->getHeader('Authorization'));
   $path=(string)$this->request->getParam('path','');
   $document=$this->mobile->projectDocumentContent((string)$auth['uid'],$id,$path);
   return new DataDisplayResponse((string)$document['content'],200,[
    'Content-Type'=>(string)$document['mime'],
    'Content-Length'=>(string)strlen((string)$document['content']),
    'Content-Disposition'=>'inline; filename="'.str_replace(['"',"\r","\n"],'',(string)$document['name']).'"',
    'X-Content-Type-Options'=>'nosniff',
    'Cache-Control'=>'private, max-age=300',
   ]);
  }catch(\Throwable $e){
   $message=$e->getMessage()!==''?$e->getMessage():'Dokument konnte nicht geladen werden.';
   $lower=strtolower($message);
   $status=str_contains($lower,'token')||str_contains($lower,'anmeldung')?401:(str_contains($lower,'berechtigung')?403:404);
   return new JSONResponse(['success'=>false,'data'=>null,'errors'=>[$message],'message'=>$message],$status);
  }
 }
 #[PublicPage,NoCSRFRequired] public function projectTimes(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectTimes((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function projectReports(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->mobileProjectReports((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function reportDetail(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->mobileReportDetail((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function signExistingReport(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->signExistingReport((string)$a['uid'],$id,$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function projectPhotos(int $id):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->projectPhotos((string)$a['uid'],$id));}
 #[PublicPage,NoCSRFRequired] public function projectPhotoContent(int $id,int $photoId):DataDisplayResponse|JSONResponse{
  try{
   $auth=$this->mobile->authenticate((string)$this->request->getHeader('Authorization'));
   $photo=$this->mobile->projectPhotoContent((string)$auth['uid'],$id,$photoId);
   return new DataDisplayResponse((string)$photo['content'],200,[
    'Content-Type'=>(string)$photo['mime'],
    'Content-Length'=>(string)strlen((string)$photo['content']),
    'Content-Disposition'=>'inline; filename="'.str_replace(['"',"\r","\n"],'',(string)$photo['name']).'"',
    'X-Content-Type-Options'=>'nosniff',
    'Cache-Control'=>'private, max-age=300',
   ]);
  }catch(\Throwable $e){
   $message=$e->getMessage()!==''?$e->getMessage():'Foto konnte nicht geladen werden.';
   $status=str_contains(strtolower($message),'token')||str_contains(strtolower($message),'anmeldung')?401:404;
   return new JSONResponse(['success'=>false,'data'=>null,'errors'=>[$message],'message'=>$message],$status);
  }
 }
 #[PublicPage,NoCSRFRequired] public function material(string $q=''):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->materials($q));}
 #[PublicPage,NoCSRFRequired] public function report():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createReport((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function time():JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->createTime((string)$a['uid'],$this->jsonBody()));}
 #[PublicPage,NoCSRFRequired] public function upload(int $projectId=0,string $type='document',string $category='Sonstige'):JSONResponse{return $this->authRun(fn(array $a)=>$this->mobile->upload((string)$a['uid'],$this->request->getUploadedFile('file')??[],$projectId,$type,$category));}
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
