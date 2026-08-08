<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Db\CustomerMapper;
use OCA\ReinhardtERP\Db\ProjectMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCA\ReinhardtERP\Service\FolderService;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\IUserManager;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\ActivityService;
use OCA\ReinhardtERP\Service\NextcloudIntegrationService;
final class PageController extends Controller {
 public function __construct(string $appName,IRequest $request,private CustomerMapper $customers,private ProjectMapper $projects,private IUserSession $users,private IDBConnection $db,private PermissionService $permissions,private FolderService $folders,private IURLGenerator $url,private ActivityService $activities,private NextcloudIntegrationService $integration,private IUserManager $userManager){parent::__construct($appName,$request);}
 #[NoAdminRequired,NoCSRFRequired] public function index():TemplateResponse{$this->permissions->assert('dashboard');return new TemplateResponse($this->appName,'dashboard',['displayName'=>$this->users->getUser()?->getDisplayName()??'Benutzer','customerCount'=>$this->count('re_erp_customers'),'projectCount'=>count($this->filterProjects($this->projects->findAllActive())),'reportCount'=>$this->count('re_erp_reports'),'openReportCount'=>$this->countWhere('re_erp_reports','locked',0),'todayHours'=>$this->todayHours(),'upcomingEvents'=>$this->upcomingEvents(),'activities'=>$this->activities->recent(12)]);}
 #[NoAdminRequired,NoCSRFRequired] public function customers():TemplateResponse{$this->permissions->assert('customers');return new TemplateResponse($this->appName,'customers',['customers'=>$this->customers->findAllActive(),'contactsEnabled'=>$this->integration->contactsEnabled(),'message'=>(string)$this->request->getParam('message','')]);}
 #[NoAdminRequired,NoCSRFRequired] public function customerForm(?int $id=null):TemplateResponse{$this->permissions->assert('customers');return new TemplateResponse($this->appName,'customer_form',['customer'=>$id?$this->customers->find($id):null,'contactsEnabled'=>$this->integration->contactsEnabled(),'addressBooks'=>$this->integration->writableAddressBooks()]);}
 #[NoAdminRequired,NoCSRFRequired] public function customerDetail(int $id):TemplateResponse{$this->permissions->assert('customers');$c=$this->customers->find($id);$projects=$this->queryProjects($id);$reports=$this->queryReportsByCustomer($id);$documents=$this->documents((string)($c->getFolderPath()??''),40,3);return new TemplateResponse($this->appName,'customer_detail',['customer'=>$c,'projects'=>$projects,'reports'=>$reports,'documents'=>$documents,'activities'=>$this->activities->forCustomer($id,60),'contacts'=>$this->customerContacts($id),'reminders'=>$this->customerReminders($id),'stats'=>$this->customerStats($id,$projects,$reports,$documents),'nextcloudContacts'=>$this->integration->contactsForSelection(),'contactsIntegrationEnabled'=>$this->integration->contactsEnabled(),'message'=>(string)$this->request->getParam('message','')]);}
 #[NoAdminRequired,NoCSRFRequired] public function projects():TemplateResponse{$this->permissions->assert('projects');
  $view=(string)$this->request->getParam('view','active');$archived=$view==='archive';$search=trim((string)$this->request->getParam('q',''));
  $projects=$this->filterProjects($archived?$this->projects->findAllArchived($search):$this->projects->findAllActive($search));
  $customerNames=[];foreach($projects as $project){try{$customerNames[$project->getCustomerId()]=$this->customers->find($project->getCustomerId())->getName();}catch(\Throwable){$customerNames[$project->getCustomerId()]='–';}}
  $activeCount=count($this->filterProjects($this->projects->findAllActive()));$archiveCount=count($this->filterProjects($this->projects->findAllArchived()));
  return new TemplateResponse($this->appName,'projects',['projects'=>$projects,'customerNames'=>$customerNames,'view'=>$archived?'archive':'active','search'=>$search,'activeCount'=>$activeCount,'archiveCount'=>$archiveCount]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function projectForm(?int $id=null,?int $customerId=null):TemplateResponse{$this->permissions->assertProjectManager();$project=$id?$this->projects->find($id):null;return new TemplateResponse($this->appName,'project_form',['project'=>$project,'customers'=>$this->customers->findAllActive(),'selectedCustomerId'=>$project?->getCustomerId()??$customerId]);}
 #[NoAdminRequired,NoCSRFRequired] public function projectDetail(int $id):TemplateResponse{
  $this->permissions->assertProjectAccess($id);
  $p=$this->queryProject($id);
  $reports=$this->queryReportsByProject($id);
  $times=$this->queryTimes($id);
  $owner=(string)($p['created_by']??'');if($owner==='')$owner=$this->users->getUser()?->getUID()??'';$allowedFolders=$this->permissions->projectFolders($id);
  $documents=$this->filterDocumentsByFolders($this->documentsForUser($owner,(string)($p['folder_path']??''),60,4),$p['folder_path']??'', $allowedFolders);
  $offers=$this->queryOffersByProject($id);
  $orders=$this->queryOrdersByProject($id);
  $events=$this->queryProjectEvents($id);
  $documentRecords=$this->filterDocumentRecordsByFolders($this->queryProjectDocuments($id),$p['folder_path']??'', $allowedFolders);
  return new TemplateResponse($this->appName,'project_detail',[
   'project'=>$p,
   'reports'=>$reports,
   'times'=>$times,
   'documents'=>$documents,
   'documentRecords'=>$documentRecords,
   'offers'=>$offers,
   'orders'=>$orders,
   'projectEvents'=>$events,
   'projectCosts'=>$this->projectCosts($id,$p,$times),
   'activities'=>$this->activities->forProject($id,80),
   'cockpit'=>$this->projectCockpit($id,$p,$reports,$times,$documents),
   'projectMembers'=>$this->projectMembers($id),
   'assignmentUsers'=>$this->assignmentUsers(),
   'canManageAssignments'=>$this->permissions->isProjectSupervisor(),
   'allowedProjectFolders'=>$allowedFolders,
   'isProjectSupervisor'=>$this->permissions->isProjectSupervisor(),
   'projectMaterials'=>$this->projectMaterials($id),
  ]);
 }

 #[NoAdminRequired] public function uploadCustomerDocument(int $id,string $targetFolder='09_Sonstiges'):RedirectResponse{$this->permissions->assert('customers');$c=$this->customers->find($id);$allowed=['01_Kundendaten','02_Anfragen','03_Angebote','04_Auftraege','06_Rechnungen','07_Korrespondenz','08_Bilder','09_Sonstiges'];if(!in_array($targetFolder,$allowed,true))$targetFolder='09_Sonstiges';$name=$this->uploadTo((string)($c->getFolderPath()??''),$targetFolder);$this->activities->record('customer',$id,'document_uploaded','Dokument hochgeladen',$name.' → '.$targetFolder,$id,null);return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.customerDetail',['id'=>$id]));}
 #[NoAdminRequired] public function uploadProjectDocument(int $id,string $targetFolder='01_Aufmass',string $documentType='other'):RedirectResponse{
  $this->permissions->assertProjectAccess($id);$p=$this->queryProject($id);
  $folderMap=['inbox'=>'00_Eingang','measurement'=>'01_Aufmass','planning'=>'02_Planung','drawing'=>'03_Zeichnungen','material'=>'04_Material','purchase'=>'05_Bestellungen','report'=>'06_Rapporte','photo'=>'07_Fotos','acceptance'=>'08_Abnahme','invoice'=>'09_Rechnung','offer'=>'10_Angebote','order'=>'11_Auftraege','other'=>'12_Sonstiges'];
  $allowed=array_values($folderMap);if(isset($folderMap[$documentType]))$targetFolder=$folderMap[$documentType];if(!in_array($targetFolder,$allowed,true)){$documentType='other';$targetFolder=$folderMap['other'];}
  if(!$this->permissions->canAccessProjectFolder($id,$targetFolder))throw new \OCP\AppFramework\Http\ForbiddenException('Dieser Projektordner ist für deinen Benutzer nicht freigegeben.');$owner=(string)($p['created_by']??'');if($owner==='')$owner=$this->users->getUser()?->getUID()??'';$name=$this->uploadToForUser($owner,(string)($p['folder_path']??''),$targetFolder);$path=trim((string)$p['folder_path'],'/').'/'.$targetFolder.'/'.$name;
  $file=$this->request->getUploadedFile('document');$mime=is_array($file)?(string)($file['type']??''):'';
  $qb=$this->db->getQueryBuilder();$qb->insert('re_erp_project_documents')->values([
   'project_id'=>$qb->createNamedParameter($id),'customer_id'=>$qb->createNamedParameter((int)$p['customer_id']),'document_type'=>$qb->createNamedParameter($documentType),'file_name'=>$qb->createNamedParameter($name),'file_path'=>$qb->createNamedParameter($path),'mime_type'=>$qb->createNamedParameter($mime!==''?$mime:null),'status'=>$qb->createNamedParameter('uploaded'),'source'=>$qb->createNamedParameter('manual'),'created_by'=>$qb->createNamedParameter($this->users->getUser()?->getUID()??'system'),'created_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();
  $labels=['offer'=>'Angebot','order'=>'Auftrag','invoice'=>'Rechnung','report'=>'Rapport','photo'=>'Foto','drawing'=>'Zeichnung'];$label=$labels[$documentType]??'Dokument';
  $this->activities->record('project',$id,'document_uploaded',$label.' hochgeladen',$name.' → '.$targetFolder,(int)$p['customer_id'],$id);
  return new RedirectResponse($this->url->linkToRoute('reinhardterp.page.projectDetail',['id'=>$id]).'#documents');
 }
 #[NoAdminRequired,NoCSRFRequired] public function projectFile(int $id,string $path):DataDownloadResponse{
  $this->permissions->assertProjectAccess($id);$p=$this->queryProject($id);$base=trim((string)($p['folder_path']??''),'/');$clean=trim($path,'/');if($base===''||!str_starts_with($clean,$base.'/'))throw new \OCP\AppFramework\Http\ForbiddenException('Ungültiger Projektpfad.');$relative=substr($clean,strlen($base)+1);$folder=explode('/',$relative,2)[0]??'';if(!$this->permissions->canAccessProjectFolder($id,$folder))throw new \OCP\AppFramework\Http\ForbiddenException('Dieser Projektordner ist nicht freigegeben.');$owner=(string)($p['created_by']??'');if($owner==='')$owner=$this->users->getUser()?->getUID()??'';$file=$this->folders->readFileForUser($owner,$clean);return new DataDownloadResponse($file['content'],$file['name'],$file['mime']);
 }
 private function uploadToForUser(string $uid,string $basePath,string $targetFolder):string{if(trim($basePath,'/')==='')throw new \InvalidArgumentException('Ordnerpfad fehlt.');$file=$this->request->getUploadedFile('document');if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new \InvalidArgumentException('Bitte eine Datei auswählen.');if((int)($file['size']??0)>100*1024*1024)throw new \InvalidArgumentException('Die Datei darf maximal 100 MB groß sein.');$folder=$this->folders->ensureFolderPathForUser($uid,$basePath,$targetFolder);$name=(string)($file['name']??'Datei');$this->folders->writeFromLocalFileForUser($uid,$folder,$name,(string)$file['tmp_name']);return $name;}
 private function uploadTo(string $basePath,string $targetFolder):string{if(trim($basePath,'/')==='')throw new \InvalidArgumentException('Ordnerpfad fehlt.');$file=$this->request->getUploadedFile('document');if(!is_array($file)||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new \InvalidArgumentException('Bitte eine Datei auswählen.');if((int)($file['size']??0)>100*1024*1024)throw new \InvalidArgumentException('Die Datei darf maximal 100 MB groß sein.');$folder=$this->folders->ensureFolderPath($basePath,$targetFolder);$name=(string)($file['name']??'Datei');$this->folders->writeFromLocalFile($folder,$name,(string)$file['tmp_name']);return $name;}
 private function documents(string $path,int $limit,int $depth):array{if(trim($path,'/')==='')return [];try{return $this->folders->listFiles($path,$limit,$depth);}catch(\Throwable){return [];}}
 private function documentsForUser(string $uid,string $path,int $limit,int $depth):array{if($uid===''||trim($path,'/')==='')return [];try{return $this->folders->listFilesForUser($uid,$path,$limit,$depth);}catch(\Throwable){return [];}}
 private function filterProjects(array $projects):array{return array_values(array_filter($projects,fn($p):bool=>$this->permissions->canAccessProject((int)$p->getId())));}
 private function filterDocumentsByFolders(array $documents,string $basePath,array $allowed):array{$base=trim($basePath,'/');if($this->permissions->isProjectSupervisor())return $documents;return array_values(array_filter($documents,static function(array $doc)use($base,$allowed):bool{$path=trim((string)($doc['path']??''),'/');if($base===''||!str_starts_with($path,$base.'/'))return false;$folder=explode('/',substr($path,strlen($base)+1),2)[0]??'';return in_array($folder,$allowed,true);}));}
 private function filterDocumentRecordsByFolders(array $rows,string $basePath,array $allowed):array{if($this->permissions->isProjectSupervisor())return $rows;$base=trim($basePath,'/');return array_values(array_filter($rows,static function(array $row)use($base,$allowed):bool{$path=trim((string)($row['file_path']??''),'/');if($base===''||!str_starts_with($path,$base.'/'))return false;$folder=explode('/',substr($path,strlen($base)+1),2)[0]??'';return in_array($folder,$allowed,true);}));}
 private function assignmentUsers():array{$rows=[];$roles=$this->permissions->roles();$this->userManager->callForSeenUsers(function($u)use(&$rows,$roles){$uid=$u->getUID();if(!isset($roles[$uid])||!$this->permissions->isEnabled($uid)||$this->permissions->isProjectSupervisor($uid))return;$rows[]=['uid'=>$uid,'displayName'=>$u->getDisplayName(),'role'=>$roles[$uid]['role']??PermissionService::EMPLOYEE];});usort($rows,static fn(array $a,array $b):int=>strcasecmp($a['displayName'],$b['displayName']));return $rows;}

 private function upcomingEvents(int $limit=8):array{
  $today=(new \DateTimeImmutable('today'))->format('Y-m-d H:i:s');
  $qb=$this->db->getQueryBuilder();
  $qb->select('*')->from('re_erp_team_events')
   ->where($qb->expr()->gte('start_at',$qb->createNamedParameter($today)))
   ->andWhere($qb->expr()->eq('is_deleted',$qb->createNamedParameter(0)))
   ->orderBy('start_at','ASC')->setMaxResults($limit);
  return $qb->executeQuery()->fetchAllAssociative();
 }
 private function count(string $table):int{$qb=$this->db->getQueryBuilder();$qb->select($qb->func()->count('*','c'))->from($table);return (int)$qb->executeQuery()->fetchOne();}
 private function queryProjects(int $customerId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_projects')->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($customerId)))->orderBy('created_at','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryProject(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('p.*','c.name AS customer_name','c.customer_no')->from('re_erp_projects','p')->leftJoin('p','re_erp_customers','c',$qb->expr()->eq('c.id','p.customer_id'))->where($qb->expr()->eq('p.id',$qb->createNamedParameter($id)));$r=$qb->executeQuery()->fetchAssociative();if(!$r)throw new \OCP\AppFramework\Http\NotFoundResponse();return $r;}
 private function queryReportsByCustomer(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('r.*','p.project_no','p.title AS project_title')->from('re_erp_reports','r')->leftJoin('r','re_erp_projects','p',$qb->expr()->eq('p.id','r.project_id'))->where($qb->expr()->eq('p.customer_id',$qb->createNamedParameter($id)))->andWhere($qb->expr()->eq('r.archived',$qb->createNamedParameter(0)))->orderBy('r.report_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryReportsByProject(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_reports')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($id)))->andWhere($qb->expr()->eq('archived',$qb->createNamedParameter(0)))->orderBy('report_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}
 private function queryTimes(int $id):array{$qb=$this->db->getQueryBuilder();$qb->select('e.*','w.work_date','w.user_id')->from('re_erp_workday_entries','e')->leftJoin('e','re_erp_workdays','w',$qb->expr()->eq('w.id','e.workday_id'))->where($qb->expr()->eq('e.project_id',$qb->createNamedParameter($id)))->orderBy('w.work_date','DESC');return $qb->executeQuery()->fetchAllAssociative();}

 private function queryOffersByProject(int $projectId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_offers')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->orderBy('offer_date','DESC')->setMaxResults(20);return $qb->executeQuery()->fetchAllAssociative();}
 private function queryOrdersByProject(int $projectId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_orders')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->orderBy('order_date','DESC')->setMaxResults(20);return $qb->executeQuery()->fetchAllAssociative();}
 private function queryProjectEvents(int $projectId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_team_events')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->andWhere($qb->expr()->eq('is_deleted',$qb->createNamedParameter(0)))->orderBy('start_at','ASC')->setMaxResults(20);return $qb->executeQuery()->fetchAllAssociative();}
 private function queryProjectDocuments(int $projectId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_project_documents')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)))->orderBy('created_at','DESC')->setMaxResults(50);return $qb->executeQuery()->fetchAllAssociative();}
 private function projectCosts(int $projectId,array $project,array $times):array{
  $hours=array_sum(array_map(static fn(array $x):float=>(float)($x['hours']??0),$times));
  $special=(float)($project['special_hourly_rate']??0);$labor=0.0;
  if($special>0){$labor=$hours*$special;}else{
   $qb=$this->db->getQueryBuilder();$qb->select('e.hours','w.user_id','ur.individual_hourly_rate','hr.sales_rate')->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$qb->expr()->eq('w.id','e.workday_id'))->leftJoin('w','re_erp_user_roles','ur',$qb->expr()->eq('ur.user_id','w.user_id'))->leftJoin('ur','re_erp_hourly_rates','hr',$qb->expr()->eq('hr.id','ur.hourly_rate_id'))->where($qb->expr()->eq('e.project_id',$qb->createNamedParameter($projectId)));
   foreach($qb->executeQuery()->fetchAllAssociative() as $r){$rate=(float)($r['individual_hourly_rate']??0);if($rate<=0)$rate=(float)($r['sales_rate']??0);$labor+=(float)$r['hours']*$rate;}
  }
  $qb=$this->db->getQueryBuilder();$qb->selectAlias($qb->createFunction('SUM(i.quantity * i.unit_price)'),'v')->from('re_erp_report_items','i')->innerJoin('i','re_erp_reports','r',$qb->expr()->eq('r.id','i.report_id'))->where($qb->expr()->eq('r.project_id',$qb->createNamedParameter($projectId)));$material=(float)($qb->executeQuery()->fetchOne()?:0);
  $qb=$this->db->getQueryBuilder();$qb->selectAlias($qb->createFunction('SUM(gross_amount)'),'v')->from('re_erp_orders')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)));$orderValue=(float)($qb->executeQuery()->fetchOne()?:0);
  $qb=$this->db->getQueryBuilder();$qb->selectAlias($qb->createFunction('SUM(gross_amount)'),'v')->from('re_erp_offers')->where($qb->expr()->eq('project_id',$qb->createNamedParameter($projectId)));$offerValue=(float)($qb->executeQuery()->fetchOne()?:0);
  $basis=$orderValue>0?$orderValue:$offerValue;return ['hours'=>$hours,'laborValue'=>$labor,'materialValue'=>$material,'offerValue'=>$offerValue,'orderValue'=>$orderValue,'projectValue'=>$basis,'remaining'=>$basis-($labor+$material)];
 }

 private function projectMembers(int $projectId):array{
  $qb=$this->db->getQueryBuilder();
  $qb->select('pu.user_id','pu.role','pu.folder_permissions')->from('re_erp_project_users','pu')->where($qb->expr()->eq('pu.project_id',$qb->createNamedParameter($projectId)))->orderBy('pu.role','ASC')->addOrderBy('pu.user_id','ASC');
  $rows=$qb->executeQuery()->fetchAllAssociative();foreach($rows as &$row){$u=$this->userManager->get((string)$row['user_id']);$row['display_name']=$u?->getDisplayName()??(string)$row['user_id'];$decoded=json_decode((string)($row['folder_permissions']??''),true);$row['folders']=is_array($decoded)?$decoded:PermissionService::EMPLOYEE_DEFAULT_FOLDERS;}unset($row);return $rows;
 }
 private function projectMaterials(int $projectId):array{
  $qb=$this->db->getQueryBuilder();
  $qb->select('i.description','i.quantity','i.unit','r.report_no','r.report_date')->from('re_erp_report_items','i')->innerJoin('i','re_erp_reports','r',$qb->expr()->eq('r.id','i.report_id'))->where($qb->expr()->eq('r.project_id',$qb->createNamedParameter($projectId)))->orderBy('r.report_date','DESC')->addOrderBy('i.id','DESC')->setMaxResults(12);
  return $qb->executeQuery()->fetchAllAssociative();
 }
 private function projectCockpit(int $projectId,array $project,array $reports,array $times,array $documents):array{
  $today=date('Y-m-d');
  $todayHours=0.0;$todayEntries=0;$users=[];$openReports=0;$signedReports=0;
  foreach($times as $entry){
   $uid=(string)($entry['user_id']??'');
   if($uid!==''){$users[$uid]=($users[$uid]??0)+(float)($entry['hours']??0);}
   if((string)($entry['work_date']??'')===$today){$todayHours+=(float)($entry['hours']??0);$todayEntries++;}
  }
  foreach($reports as $report){
   if(empty($report['locked']))$openReports++;
   if(!empty($report['signature_data'])||strcasecmp((string)($report['status']??''),'Unterschrieben')===0)$signedReports++;
  }
  $photos=array_values(array_filter($documents,static fn(array $doc):bool=>str_starts_with((string)($doc['mime']??''),'image/')));
  $dueDate=(string)($project['due_date']??'');
  $daysToDue=null;
  if($dueDate!==''){
   $dueTs=strtotime($dueDate);
   if($dueTs!==false)$daysToDue=(int)floor(($dueTs-strtotime($today))/86400);
  }
  arsort($users);
  return [
   'todayHours'=>$todayHours,
   'todayEntries'=>$todayEntries,
   'teamHours'=>$users,
   'openReports'=>$openReports,
   'signedReports'=>$signedReports,
   'photoCount'=>count($photos),
   'photos'=>array_slice($photos,0,8),
   'daysToDue'=>$daysToDue,
   'dueDate'=>$dueDate,
   'startDate'=>(string)($project['start_date']??''),
  ];
 }

 private function customerContacts(int $customerId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_customer_contacts')->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($customerId)))->orderBy('is_primary','DESC')->addOrderBy('name','ASC');return $qb->executeQuery()->fetchAllAssociative();}
 private function customerReminders(int $customerId):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_customer_reminders')->where($qb->expr()->eq('customer_id',$qb->createNamedParameter($customerId)))->orderBy('is_done','ASC')->addOrderBy('due_date','ASC');return $qb->executeQuery()->fetchAllAssociative();}
 private function customerStats(int $customerId,array $projects,array $reports,array $documents):array{$qb=$this->db->getQueryBuilder();$qb->select($qb->func()->sum('e.hours','s'))->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$qb->expr()->eq('w.id','e.workday_id'))->innerJoin('e','re_erp_projects','p',$qb->expr()->eq('p.id','e.project_id'))->where($qb->expr()->eq('p.customer_id',$qb->createNamedParameter($customerId)));$hours=(float)($qb->executeQuery()->fetchOne()?:0);$openReports=0;foreach($reports as $report){if(empty($report['locked']))$openReports++;}return ['projects'=>count($projects),'reports'=>count($reports),'openReports'=>$openReports,'documents'=>count($documents),'hours'=>$hours];}
 private function countWhere(string $table,string $column,int $value):int{$qb=$this->db->getQueryBuilder();$qb->select($qb->func()->count('*','c'))->from($table)->where($qb->expr()->eq($column,$qb->createNamedParameter($value)));return (int)$qb->executeQuery()->fetchOne();}
 private function todayHours():float{$qb=$this->db->getQueryBuilder();$qb->select($qb->func()->sum('e.hours','s'))->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$qb->expr()->eq('w.id','e.workday_id'))->where($qb->expr()->eq('w.work_date',$qb->createNamedParameter(date('Y-m-d'))));return (float)($qb->executeQuery()->fetchOne()?:0);}

}
