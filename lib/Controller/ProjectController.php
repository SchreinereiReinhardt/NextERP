<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Db\Project;
use OCA\ReinhardtERP\Db\ProjectMapper;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\ActivityService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserSession;
final class ProjectController extends Controller {
 private const STATUSES=['Anfrage','Angebot','Auftrag','Fertigung','Montage','Abnahme','Abrechnung','Abgeschlossen','offen','in Planung','in Arbeit','wartet','abgeschlossen'];
 public function __construct(string $appName,IRequest $request,private ProjectMapper $mapper,private CustomerMapper $customers,private IUserSession $users,private IURLGenerator $url,private FolderService $folders,private NumberService $numbers,private PermissionService $permissions,private ActivityService $activities,private IDBConnection $db){parent::__construct($appName,$request);}
 #[NoAdminRequired]
 public function save(?int $id,int $customerId,?string $projectNo,string $title,string $status='Anfrage',?string $startDate=null,?string $dueDate=null,?string $description=null):RedirectResponse{$this->permissions->assertProjectManager();
  $title=trim($title);if($customerId<=0||$title==='')throw new \InvalidArgumentException('Kunde und Titel sind Pflicht.');if(!in_array($status,self::STATUSES,true))$status='Anfrage';
  $now=new \DateTime();$p=$id!==null?$this->mapper->find($id):new Project();$projectNo=$id===null?$this->numbers->next('project'):$p->getProjectNo();$p->setCustomerId($customerId);$p->setProjectNo($projectNo);$p->setTitle($title);$p->setStatus($status);$p->setStartDate($this->d($startDate));$p->setDueDate($this->d($dueDate));$p->setDescription($this->n($description));$p->setUpdatedAt($now);
  $customer=$this->customers->find($customerId);$path=$customer->getFolderPath() ?: $this->folders->ensureCustomerFolder((string)$customer->getCustomerNo(),$customer->getName());$p->setFolderPath($this->folders->ensureProjectFolder($path,$projectNo,$title));
  if($id===null){$p->setCreatedAt($now);$p->setCreatedBy($this->users->getUser()?->getUID()??'system');$this->mapper->insert($p);$this->activities->record('project',$p->getId(),'created','Projekt erstellt',$projectNo.' · '.$title,$customerId,$p->getId());}else{$this->mapper->update($p);$this->activities->record('project',$p->getId(),'updated','Projekt geändert',$projectNo.' · '.$title,$customerId,$p->getId());}return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$p->getId()]));
 }
 #[NoAdminRequired] public function archive(int $id):RedirectResponse{$this->permissions->assertProjectManager();$p=$this->mapper->find($id);$p->setIsArchived(true);$p->setUpdatedAt(new \DateTime());$this->mapper->update($p);$this->activities->record('project',$id,'archived','Projekt archiviert',$p->getProjectNo().' · '.$p->getTitle(),$p->getCustomerId(),$id);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projects'));}
 #[NoAdminRequired] public function restore(int $id):RedirectResponse{$this->permissions->assertProjectManager();$p=$this->mapper->find($id);$p->setIsArchived(false);$p->setUpdatedAt(new \DateTime());$this->mapper->update($p);$this->activities->record('project',$id,'restored','Projekt wiederhergestellt',$p->getProjectNo().' · '.$p->getTitle(),$p->getCustomerId(),$id);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projects',['view'=>'archive']));}
 #[NoAdminRequired] public function updateStatus(int $id,string $status):RedirectResponse{$this->permissions->assertProjectManager();$allowed=['Anfrage','Angebot','Auftrag','Fertigung','Montage','Abnahme','Abrechnung','Abgeschlossen','offen','in Planung','in Arbeit','wartet','abgeschlossen'];if(!in_array($status,$allowed,true))throw new \InvalidArgumentException('Ungültiger Projektstatus.');$p=$this->mapper->find($id);$old=(string)$p->getStatus();$p->setStatus($status);$p->setUpdatedAt(new \DateTime());$this->mapper->update($p);$this->activities->record('project',$id,'status_changed','Status geändert',$old.' → '.$status,$p->getCustomerId(),$id);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$id]));}

 #[NoAdminRequired] public function saveAssignments(int $id):RedirectResponse{
  $this->permissions->assertProjectManager();$project=$this->mapper->find($id);
  $selected=array_values(array_unique(array_filter(array_map('strval',(array)$this->request->getParam('projectUsers',[])),static fn(string $v):bool=>$v!=='')));
  $folders=(array)$this->request->getParam('folders',[]);
  $del=$this->db->getQueryBuilder();$del->delete('re_erp_project_users')->where($del->expr()->eq('project_id',$del->createNamedParameter($id)))->executeStatement();
  foreach($selected as $uid){$allowed=array_values(array_intersect(PermissionService::PROJECT_FOLDERS,array_map('strval',(array)($folders[$uid]??PermissionService::EMPLOYEE_DEFAULT_FOLDERS))));if($allowed===[])$allowed=PermissionService::EMPLOYEE_DEFAULT_FOLDERS;$q=$this->db->getQueryBuilder();$q->insert('re_erp_project_users')->values(['project_id'=>$q->createNamedParameter($id),'user_id'=>$q->createNamedParameter($uid),'role'=>$q->createNamedParameter('Mitarbeiter'),'folder_permissions'=>$q->createNamedParameter(json_encode($allowed,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))])->executeStatement();}
  $this->activities->record('project',$id,'permissions_changed','Projektfreigaben geändert',count($selected).' Mitarbeiter freigegeben',$project->getCustomerId(),$id);
  return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$id]).'#permissions');
 }

 private function d(?string $v):?\DateTime{if($v===null||trim($v)==='')return null;$d=\DateTime::createFromFormat('!Y-m-d',$v);if($d===false)throw new \InvalidArgumentException('Ungültiges Datum.');return $d;}
 private function n(?string $v):?string{if($v===null)return null;$v=trim($v);return $v===''?null:$v;}
}
