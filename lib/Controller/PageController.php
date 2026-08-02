<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Db\ProjectMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;
use OCA\ReinhardtERP\Service\PermissionService;
final class PageController extends Controller {
 public function __construct(string $appName,IRequest $request,private CustomerMapper $customers,private ProjectMapper $projects,private IUserSession $users,private IDBConnection $db,private PermissionService $permissions){parent::__construct($appName,$request);}
 #[NoAdminRequired,NoCSRFRequired] public function index():TemplateResponse{$this->permissions->assert('dashboard');return new TemplateResponse($this->appName,'dashboard',['displayName'=>$this->users->getUser()?->getDisplayName()??'Benutzer','customerCount'=>$this->count('re_erp_customers'),'projectCount'=>$this->count('re_erp_projects'),'reportCount'=>$this->count('re_erp_reports')]);}
 #[NoAdminRequired,NoCSRFRequired] public function customers():TemplateResponse{$this->permissions->assert('customers');return new TemplateResponse($this->appName,'customers',['customers'=>$this->customers->findAllActive()]);}
 #[NoAdminRequired,NoCSRFRequired] public function customerForm(?int $id=null):TemplateResponse{$this->permissions->assert('customers');return new TemplateResponse($this->appName,'customer_form',['customer'=>$id?$this->customers->find($id):null]);}
 #[NoAdminRequired,NoCSRFRequired] public function customerDetail(int $id):TemplateResponse{$this->permissions->assert('customers');$c=$this->customers->find($id);return new TemplateResponse($this->appName,'customer_detail',['customer'=>$c,'projects'=>$this->queryProjects($id),'reports'=>$this->queryReportsByCustomer($id)]);}
 #[NoAdminRequired,NoCSRFRequired] public function projects():TemplateResponse{$this->permissions->assert('projects');
  $projects=$this->projects->findAllActive();
  $customerNames=[];
  foreach($this->customers->findAllActive() as $customer){
   $customerNames[$customer->getId()]=$customer->getName();
  }
  return new TemplateResponse($this->appName,'projects',['projects'=>$projects,'customerNames'=>$customerNames]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function projectForm(?int $id=null):TemplateResponse{$this->permissions->assert('projects');return new TemplateResponse($this->appName,'project_form',['project'=>$id?$this->projects->find($id):null,'customers'=>$this->customers->findAllActive()]);}
 #[NoAdminRequired,NoCSRFRequired] public function projectDetail(int $id):TemplateResponse{$this->permissions->assert('projects');$p=$this->queryProject($id);return new TemplateResponse($this->appName,'project_detail',['project'=>$p,'reports'=>$this->queryReportsByProject($id),'times'=>$this->queryTimes($id)]);}
 private function count(string $table):int{$qb=$this->db->getQueryBuilder();$qb->select($qb->func()->count('*','c'))->from($table);return (int)$qb->executeQuery()->fetchOne();}
 private function queryProjects(int $customerId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_projects')->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($customerId)))->orderBy('created_at','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryProject(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('p.*','c.name AS customer_name','c.customer_no')->from('re_erp_projects','p')->leftJoin('p','re_erp_customers','c',$qb->expr()->eq('c.id','p.customer_id'))->where($qb->expr()->eq('p.id',$qb->createNamedParameter($id)));$r=$qb->executeQuery()->fetchAssociative();if(!$r)throw new \OCP\AppFramework\Http\NotFoundResponse();return $r;}
 private function queryReportsByCustomer(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('r.*','p.project_no','p.title AS project_title')->from('re_erp_reports','r')->leftJoin('r','re_erp_projects','p',$qb->expr()->eq('p.id','r.project_id'))->where($qb->expr()->eq('p.customer_id',$qb->createNamedParameter($id)))->orderBy('r.report_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryReportsByProject(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_reports')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($id)))->orderBy('report_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryTimes(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('e.*','w.work_date','w.user_id')->from('re_erp_workday_entries','e')->leftJoin('e','re_erp_workdays','w',$qb->expr()->eq('w.id','e.workday_id'))->where($qb->expr()->eq('e.project_id',$qb->createNamedParameter($id)))->orderBy('w.work_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}
}
