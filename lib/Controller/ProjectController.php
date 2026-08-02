<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Db\Project;
use OCA\ReinhardtERP\Db\ProjectMapper;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
final class ProjectController extends Controller {
 private const STATUSES=['offen','in Planung','in Arbeit','wartet','abgeschlossen'];
 public function __construct(string $appName,IRequest $request,private ProjectMapper $mapper,private CustomerMapper $customers,private IUserSession $users,private IURLGenerator $url,private FolderService $folders,private NumberService $numbers,private PermissionService $permissions){parent::__construct($appName,$request);}
 #[NoAdminRequired]
 public function save(?int $id,int $customerId,?string $projectNo,string $title,string $status='offen',?string $startDate=null,?string $dueDate=null,?string $description=null):RedirectResponse{$this->permissions->assert('projects');
  $title=trim($title);if($customerId<=0||$title==='')throw new \InvalidArgumentException('Kunde und Titel sind Pflicht.');if(!in_array($status,self::STATUSES,true))$status='offen';
  $now=new \DateTime();$p=$id!==null?$this->mapper->find($id):new Project();$projectNo=$id===null?$this->numbers->next('project'):$p->getProjectNo();$p->setCustomerId($customerId);$p->setProjectNo($projectNo);$p->setTitle($title);$p->setStatus($status);$p->setStartDate($this->d($startDate));$p->setDueDate($this->d($dueDate));$p->setDescription($this->n($description));$p->setUpdatedAt($now);
  $customer=$this->customers->find($customerId);$path=$customer->getFolderPath() ?: $this->folders->ensureCustomerFolder((string)$customer->getCustomerNo(),$customer->getName());$p->setFolderPath($this->folders->ensureProjectFolder($path,$projectNo,$title));
  if($id===null){$p->setCreatedAt($now);$p->setCreatedBy($this->users->getUser()?->getUID()??'system');$this->mapper->insert($p);}else{$this->mapper->update($p);}return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projects'));
 }
 #[NoAdminRequired] public function archive(int $id):RedirectResponse{$this->permissions->assert('projects');$p=$this->mapper->find($id);$p->setIsArchived(true);$p->setUpdatedAt(new \DateTime());$this->mapper->update($p);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projects'));}
 private function d(?string $v):?\DateTime{if($v===null||trim($v)==='')return null;$d=\DateTime::createFromFormat('!Y-m-d',$v);if($d===false)throw new \InvalidArgumentException('Ungültiges Datum.');return $d;}
 private function n(?string $v):?string{if($v===null)return null;$v=trim($v);return $v===''?null:$v;}
}
