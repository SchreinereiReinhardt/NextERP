<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

final class MobileService {
 private const ACCESS_TTL=3600;
 private const REFRESH_TTL=2592000;
 public function __construct(
  private IDBConnection $db,
  private IUserManager $users,
  private IGroupManager $groups,
  private IConfig $config,
  private IRootFolder $rootFolder,
  private NumberService $numbers,
  private FolderService $folders,
  private PdfService $pdf,
 ){}
 public function login(string $username,string $password,?string $deviceName=null):array{
  $username=trim($username);
  if($username===''||$password==='')throw new \InvalidArgumentException('Benutzername und Passwort sind erforderlich.');
  $user=$this->users->checkPassword($username,$password);
  if(!$user instanceof IUser)throw new \RuntimeException('Anmeldung fehlgeschlagen.');
  if(!$user->isEnabled())throw new \RuntimeException('Benutzer ist deaktiviert.');
  return $this->issueTokens($user,$deviceName);
 }
 public function refresh(string $refreshToken):array{
  $hash=hash('sha256',$refreshToken);$now=date('Y-m-d H:i:s');
  $qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_mobile_tokens')->where($qb->expr()->eq('refresh_hash',$qb->createNamedParameter($hash)))->andWhere($qb->expr()->isNull('revoked_at'))->andWhere($qb->expr()->gt('refresh_expires_at',$qb->createNamedParameter($now)));
  $row=$qb->executeQuery()->fetchAssociative();if(!$row)throw new \RuntimeException('Refresh-Token ist ungültig oder abgelaufen.');
  $user=$this->users->get((string)$row['user_id']);if(!$user instanceof IUser||!$user->isEnabled())throw new \RuntimeException('Benutzer ist nicht verfügbar.');
  $this->revokeById((int)$row['id']);return $this->issueTokens($user,(string)($row['device_name']??''));
 }
 public function authenticate(string $authorization):array{
  if(!preg_match('/^Bearer\s+(.+)$/i',trim($authorization),$m))throw new \RuntimeException('Bearer-Token fehlt.');
  $hash=hash('sha256',trim($m[1]));$now=date('Y-m-d H:i:s');
  $qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_mobile_tokens')->where($qb->expr()->eq('token_hash',$qb->createNamedParameter($hash)))->andWhere($qb->expr()->isNull('revoked_at'))->andWhere($qb->expr()->gt('expires_at',$qb->createNamedParameter($now)));
  $row=$qb->executeQuery()->fetchAssociative();if(!$row)throw new \RuntimeException('Token ist ungültig oder abgelaufen.');
  $user=$this->users->get((string)$row['user_id']);if(!$user instanceof IUser||!$user->isEnabled())throw new \RuntimeException('Benutzer ist nicht verfügbar.');
  $up=$this->db->getQueryBuilder();$up->update('re_erp_mobile_tokens')->set('last_used_at',$up->createNamedParameter($now))->where($up->expr()->eq('id',$up->createNamedParameter((int)$row['id'])))->executeStatement();
  return ['tokenId'=>(int)$row['id'],'uid'=>$user->getUID(),'user'=>$this->userPayload($user),'role'=>$this->role($user->getUID()),'permissions'=>$this->permissions($user->getUID())];
 }
 public function logout(int $tokenId):void{$this->revokeById($tokenId);}
 public function bootstrap(string $uid):array{return ['user'=>$this->userPayload($this->requiredUser($uid)),'role'=>$this->role($uid),'permissions'=>$this->permissions($uid),'documentTypes'=>['offer','order','incoming_invoice','outgoing_invoice','delivery_note','credit_note','bank_statement','report','drawing','photo','other'],'projectStates'=>['anfrage','angebot','auftrag','fertigung','montage','abnahme','abrechnung','abgeschlossen'],'materialGroups'=>$this->simpleList('re_erp_material_groups','id','name','active'),'serverVersion'=>$this->version(),'apiVersion'=>1];}
 public function dashboard(string $uid):array{
  $today=date('Y-m-d');
  return ['projectsToday'=>$this->countProjectsToday($uid,$today),'tasks'=>$this->countFutureEvents($today),'documents'=>$this->countWhere('re_erp_documents','status','new'),'reportsOpen'=>$this->countWhere('re_erp_reports','locked',0),'todayHours'=>$this->todayHours($uid,$today),'appointments'=>$this->appointments($today,8),'recentProjects'=>$this->projects($uid,8)];
 }
 public function customers(string $uid):array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from('re_erp_customers')->where($q->expr()->eq('is_archived',$q->createNamedParameter(0)))->orderBy('name','ASC')->setMaxResults(500);
  return array_map(static fn(array $r):array=>[
   'id'=>(int)$r['id'],'customerNo'=>(string)($r['customer_no']??''),'name'=>(string)($r['name']??''),
   'contactName'=>(string)($r['contact_name']??''),'phone'=>(string)($r['phone']??''),'mobile'=>(string)($r['mobile']??''),
   'email'=>(string)($r['email']??''),'street'=>(string)($r['street']??''),'postalCode'=>(string)($r['postal_code']??''),
   'city'=>(string)($r['city']??''),'country'=>(string)($r['country']??''),'notes'=>(string)($r['notes']??''),
  ],$q->executeQuery()->fetchAllAssociative());
 }

 public function createCustomer(string $uid,array $data):array{
  $this->assertMasterDataWrite($uid);
  $name=trim((string)($data['name']??''));if($name==='')throw new \InvalidArgumentException('Kundenname ist erforderlich.');
  $number=$this->numbers->next('customer');
  $street=trim((string)($data['street']??''));$postal=trim((string)($data['postalCode']??''));$city=trim((string)($data['city']??''));$country=trim((string)($data['country']??''));
  $address=trim(implode("\n",array_filter([$street,trim($postal.' '.$city),$country],static fn(string $v):bool=>$v!=='')));
  $folder=$this->folders->ensureCustomerFolder($number,$name);$now=date('Y-m-d H:i:s');
  $q=$this->db->getQueryBuilder();$q->insert('re_erp_customers')->values([
   'customer_no'=>$q->createNamedParameter($number),'name'=>$q->createNamedParameter($name),'folder_path'=>$q->createNamedParameter($folder),
   'contact_name'=>$q->createNamedParameter($this->nullString($data['contactName']??null)),'phone'=>$q->createNamedParameter($this->nullString($data['phone']??null)),
   'mobile'=>$q->createNamedParameter($this->nullString($data['mobile']??null)),'email'=>$q->createNamedParameter($this->nullString($data['email']??null)),
   'street'=>$q->createNamedParameter($this->nullString($street)),'postal_code'=>$q->createNamedParameter($this->nullString($postal)),
   'city'=>$q->createNamedParameter($this->nullString($city)),'country'=>$q->createNamedParameter($this->nullString($country)),
   'address'=>$q->createNamedParameter($this->nullString($address)),'notes'=>$q->createNamedParameter($this->nullString($data['notes']??null)),
   'is_archived'=>$q->createNamedParameter(0),'created_by'=>$q->createNamedParameter($uid),'created_at'=>$q->createNamedParameter($now),'updated_at'=>$q->createNamedParameter($now),
  ])->executeStatement();
  return ['id'=>(int)$this->db->lastInsertId('*PREFIX*re_erp_customers'),'customerNo'=>$number,'name'=>$name,'contactName'=>(string)($data['contactName']??''),'phone'=>(string)($data['phone']??''),'mobile'=>(string)($data['mobile']??''),'email'=>(string)($data['email']??''),'street'=>$street,'postalCode'=>$postal,'city'=>$city,'country'=>$country,'notes'=>(string)($data['notes']??'')];
 }

 public function createProject(string $uid,array $data):array{
  $this->assertMasterDataWrite($uid);
  $customerId=(int)($data['customerId']??0);$title=trim((string)($data['title']??''));
  if($customerId<=0||$title==='')throw new \InvalidArgumentException('Kunde und Projektname sind erforderlich.');
  $allowed=['Anfrage','Angebot','Auftrag','Fertigung','Montage','Abnahme','Abrechnung','Abgeschlossen'];$status=(string)($data['status']??'Anfrage');if(!in_array($status,$allowed,true))$status='Anfrage';
  $cq=$this->db->getQueryBuilder();$cq->select('*')->from('re_erp_customers')->where($cq->expr()->eq('id',$cq->createNamedParameter($customerId)));$customer=$cq->executeQuery()->fetchAssociative();if(!$customer)throw new \InvalidArgumentException('Kunde wurde nicht gefunden.');
  $projectNo=$this->numbers->next('project');$customerFolder=(string)($customer['folder_path']??'');if($customerFolder==='')$customerFolder=$this->folders->ensureCustomerFolder((string)$customer['customer_no'],(string)$customer['name']);
  $projectFolder=$this->folders->ensureProjectFolder($customerFolder,$projectNo,$title);$now=date('Y-m-d H:i:s');
  $q=$this->db->getQueryBuilder();$q->insert('re_erp_projects')->values([
   'customer_id'=>$q->createNamedParameter($customerId),'project_no'=>$q->createNamedParameter($projectNo),'title'=>$q->createNamedParameter($title),'status'=>$q->createNamedParameter($status),
   'start_date'=>$q->createNamedParameter($this->dateOrNull($data['startDate']??null)),'due_date'=>$q->createNamedParameter($this->dateOrNull($data['dueDate']??null)),
   'description'=>$q->createNamedParameter($this->nullString($data['description']??null)),'folder_path'=>$q->createNamedParameter($projectFolder),'is_archived'=>$q->createNamedParameter(0),
   'created_by'=>$q->createNamedParameter($uid),'created_at'=>$q->createNamedParameter($now),'updated_at'=>$q->createNamedParameter($now),
  ])->executeStatement();
  return $this->projectSummary([
   'id'=>(int)$this->db->lastInsertId('*PREFIX*re_erp_projects'),'project_no'=>$projectNo,'title'=>$title,'status'=>$status,
   'start_date'=>$this->dateOrNull($data['startDate']??null),'due_date'=>$this->dateOrNull($data['dueDate']??null),
   'customer_name'=>(string)$customer['name'],'customer_phone'=>(string)($customer['phone']??''),'customer_mobile'=>(string)($customer['mobile']??''),
   'customer_email'=>(string)($customer['email']??''),'street'=>(string)($customer['street']??''),'postal_code'=>(string)($customer['postal_code']??''),'city'=>(string)($customer['city']??''),
  ]);
 }

 private function assertMasterDataWrite(string $uid):void{
  if(!in_array($this->role($uid),['administrator','admin','office','manager'],true))throw new \RuntimeException('Keine Berechtigung zum Anlegen.');
 }
 private function nullString(mixed $value):?string{$value=trim((string)$value);return $value===''?null:$value;}
 private function dateOrNull(mixed $value):?string{$value=trim((string)$value);if($value==='')return null;$date=\DateTime::createFromFormat('!Y-m-d',$value);if($date===false)throw new \InvalidArgumentException('Ungültiges Datum.');return $date->format('Y-m-d');}

 public function projects(string $uid,int $limit=100):array{
  $role=$this->role($uid);$qb=$this->db->getQueryBuilder();$qb->select('p.*','c.name AS customer_name','c.phone AS customer_phone','c.mobile AS customer_mobile','c.email AS customer_email','c.street','c.postal_code','c.city')->from('re_erp_projects','p')->leftJoin('p','re_erp_customers','c',$qb->expr()->eq('c.id','p.customer_id'));
  $qb->where($qb->expr()->eq('p.is_archived',$qb->createNamedParameter(0)));
  if(!in_array($role,['administrator','admin','office','manager'],true)){$qb->innerJoin('p','re_erp_project_users','pu',$qb->expr()->andX($qb->expr()->eq('pu.project_id','p.id'),$qb->expr()->eq('pu.user_id',$qb->createNamedParameter($uid))));}
  $qb->orderBy('p.due_date','ASC')->addOrderBy('p.id','DESC')->setMaxResults(max(1,min($limit,250)));
  return array_map(fn(array $r)=>$this->projectSummary($r),$qb->executeQuery()->fetchAllAssociative());
 }
 public function project(string $uid,int $id):array{
  $this->assertProjectAccess($uid,$id);$qb=$this->db->getQueryBuilder();$qb->select('p.*','c.name AS customer_name','c.contact_name','c.phone','c.mobile','c.email','c.street','c.postal_code','c.city','c.country')->from('re_erp_projects','p')->leftJoin('p','re_erp_customers','c',$qb->expr()->eq('c.id','p.customer_id'))->where($qb->expr()->eq('p.id',$qb->createNamedParameter($id)));$p=$qb->executeQuery()->fetchAssociative();if(!$p)throw new \RuntimeException('Projekt nicht gefunden.');
  $out=$this->projectSummary($p);$out['description']=$p['description']??null;$out['documents']=$this->projectDocuments($uid,$id);$out['photos']=$this->projectPhotos($uid,$id);$out['material']=$this->projectMaterial($id);$out['appointments']=$this->projectEvents($id);$out['reports']=$this->projectReports($id);return $out;
 }
 public function projectDocuments(string $uid,int $id):array{
  $this->assertProjectAccess($uid,$id);
  $project=$this->projectRow($id);
  $documents=[];
  $knownPaths=[];

  $qb=$this->db->getQueryBuilder();
  $qb->select('*')->from('re_erp_project_documents')
   ->where($qb->expr()->eq('project_id',$qb->createNamedParameter($id)))
   ->orderBy('created_at','DESC')->setMaxResults(200);
  foreach($qb->executeQuery()->fetchAllAssociative() as $row){
   $path=trim((string)($row['file_path']??''),'/');
   if($path!=='')$knownPaths[$path]=true;
   $documents[]=$this->mobileDocumentRow($row,$id);
  }

  $base=trim((string)($project['folder_path']??''),'/');
  if($base!==''){
   try{
    $folder=$this->existingFolder($uid,$base);
    $files=[];
    $this->collectProjectFiles($folder,$base,0,5,$files,250);
    foreach($files as $file){
     $path=trim((string)$file['file_path'],'/');
     if(isset($knownPaths[$path]))continue;
     $documents[]=$this->mobileDocumentRow($file,$id);
     $knownPaths[$path]=true;
    }
   }catch(\OCP\Files\NotFoundException|\RuntimeException){
    // Ein fehlender Projektordner darf die API nicht vollständig blockieren.
   }
  }

  usort($documents,static function(array $a,array $b):int{
   $aTime=strtotime((string)($a['created_at']??''))?:0;
   $bTime=strtotime((string)($b['created_at']??''))?:0;
   return $bTime<=>$aTime;
  });
  return array_slice($documents,0,200);
 }
 public function projectPhotos(string $uid,int $id):array{return array_values(array_filter($this->projectDocuments($uid,$id),static fn(array $d):bool=>str_starts_with((string)($d['mime_type']??''),'image/')||(string)($d['document_type']??'')==='photo'));}
 public function projectPhotoContent(string $uid,int $projectId,int $photoId):array{
  $this->assertProjectAccess($uid,$projectId);
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from('re_erp_project_documents')
   ->where($q->expr()->eq('id',$q->createNamedParameter($photoId)))
   ->andWhere($q->expr()->eq('project_id',$q->createNamedParameter($projectId)));
  $row=$q->executeQuery()->fetchAssociative();
  if(!$row)throw new \RuntimeException('Foto nicht gefunden.');
  $mime=(string)($row['mime_type']??'');
  $type=(string)($row['document_type']??'');
  if(!str_starts_with($mime,'image/')&&$type!=='photo')throw new \RuntimeException('Datei ist kein Foto.');
  $path=trim((string)($row['file_path']??''),'/');
  if($path==='')throw new \RuntimeException('Fotopfad fehlt.');
  $node=$this->rootFolder->getUserFolder($uid);
  foreach(explode('/',$path) as $part){if($part==='')continue;$node=$node->get($part);}
  if(!$node instanceof File)throw new \RuntimeException('Fotodatei nicht gefunden.');
  $content=$node->getContent();
  $name=$node->getName();
  $extension=strtolower(pathinfo($name,PATHINFO_EXTENSION));
  $resolvedMime=match($extension){
   'jpg','jpeg'=>'image/jpeg',
   'png'=>'image/png',
   'webp'=>'image/webp',
   'gif'=>'image/gif',
   default=>$node->getMimeType()?:$mime?:'application/octet-stream',
  };
  return ['content'=>$content,'mime'=>$resolvedMime,'name'=>$name];
 }
 public function materials(?string $query=null):array{$qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_materials')->where($qb->expr()->eq('active',$qb->createNamedParameter(1)));if(trim((string)$query)!==''){$q='%'.strtolower(trim((string)$query)).'%';$qb->andWhere($qb->expr()->orX($qb->expr()->like($qb->func()->lower('name'),$qb->createNamedParameter($q)),$qb->expr()->like($qb->func()->lower('article_no'),$qb->createNamedParameter($q)),$qb->expr()->like($qb->func()->lower('barcode'),$qb->createNamedParameter($q))));}$qb->orderBy('name','ASC')->setMaxResults(200);return $qb->executeQuery()->fetchAllAssociative();}
 public function mobileProjectReports(string $uid,int $projectId):array{
  $this->assertProjectAccess($uid,$projectId);
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from('re_erp_reports')->where($q->expr()->eq('project_id',$q->createNamedParameter($projectId)))->andWhere($q->expr()->eq('archived',$q->createNamedParameter(0)))->orderBy('report_date','DESC')->addOrderBy('id','DESC')->setMaxResults(100);
  return array_map(static fn(array $r):array=>[
   'id'=>(int)$r['id'],'reportNo'=>(string)($r['report_no']??''),'reportDate'=>(string)($r['report_date']??''),
   'title'=>(string)($r['title']??''),'status'=>(string)($r['status']??'Entwurf'),
   'signedBy'=>(string)($r['signed_by']??''),'signedAt'=>(string)($r['signed_at']??''),'locked'=>(bool)($r['locked']??false),
  ],$q->executeQuery()->fetchAllAssociative());
 }

 public function mobileReportDetail(string $uid,int $reportId):array{
  $report=$this->mobileReportRow($reportId);$this->assertProjectAccess($uid,(int)$report['project_id']);
  $hq=$this->db->getQueryBuilder();$hq->select('*')->from('re_erp_report_hours')->where($hq->expr()->eq('report_id',$hq->createNamedParameter($reportId)))->orderBy('work_date','ASC')->addOrderBy('id','ASC');
  $hours=array_map(static fn(array $h):array=>['workDate'=>(string)($h['work_date']??''),'userId'=>(string)($h['user_id']??''),'hours'=>(float)($h['hours']??0),'activity'=>(string)($h['activity']??'')],$hq->executeQuery()->fetchAllAssociative());
  $iq=$this->db->getQueryBuilder();$iq->select('*')->from('re_erp_report_items')->where($iq->expr()->eq('report_id',$iq->createNamedParameter($reportId)))->orderBy('id','ASC');
  $items=array_map(static fn(array $i):array=>['description'=>(string)($i['description']??''),'quantity'=>(float)($i['quantity']??0),'unit'=>(string)($i['unit']??''),'notes'=>(string)($i['notes']??'')],$iq->executeQuery()->fetchAllAssociative());
  $fq=$this->db->getQueryBuilder();$fq->select($fq->func()->count('*','c'))->from('re_erp_report_files')->where($fq->expr()->eq('report_id',$fq->createNamedParameter($reportId)));
  return ['id'=>(int)$report['id'],'projectId'=>(int)$report['project_id'],'reportNo'=>(string)($report['report_no']??''),'reportDate'=>(string)($report['report_date']??''),'title'=>(string)($report['title']??''),'description'=>(string)($report['description']??''),'customerNote'=>(string)($report['customer_note']??''),'status'=>(string)($report['status']??'Entwurf'),'signedBy'=>(string)($report['signed_by']??''),'signedAt'=>(string)($report['signed_at']??''),'technicianSignedBy'=>(string)($report['technician_signed_by']??''),'technicianSignedAt'=>(string)($report['technician_signed_at']??''),'hours'=>$hours,'items'=>$items,'photoCount'=>(int)$fq->executeQuery()->fetchOne()];
 }

 public function signExistingReport(string $uid,int $reportId,array $data):array{
  $report=$this->mobileReportRow($reportId);$this->assertProjectAccess($uid,(int)$report['project_id']);
  if(strtolower((string)($report['status']??''))==='unterschrieben')throw new \InvalidArgumentException('Der Rapport ist bereits unterschrieben.');
  $customerSignature=$this->mobileSignature((string)($data['customerSignatureData']??''),'Kundenunterschrift');
  $technicianSignature=$this->mobileSignature((string)($data['technicianSignatureData']??''),'Monteurunterschrift');
  $customerSignedBy=trim((string)($data['customerSignedBy']??''));$technicianSignedBy=trim((string)($data['technicianSignedBy']??''));
  if($customerSignature===null||$customerSignedBy==='')throw new \InvalidArgumentException('Kundenname und Kundenunterschrift sind erforderlich.');
  if($technicianSignature===null||$technicianSignedBy==='')throw new \InvalidArgumentException('Monteur und Monteurunterschrift sind erforderlich.');
  $now=date('Y-m-d H:i:s');
  $u=$this->db->getQueryBuilder();$u->update('re_erp_reports')
   ->set('signature_data',$u->createNamedParameter($customerSignature))->set('signature_mime',$u->createNamedParameter('image/png'))
   ->set('signed_by',$u->createNamedParameter($customerSignedBy))->set('signed_at',$u->createNamedParameter($now))
   ->set('technician_signature_data',$u->createNamedParameter($technicianSignature))->set('technician_signature_mime',$u->createNamedParameter('image/png'))
   ->set('technician_signed_by',$u->createNamedParameter($technicianSignedBy))->set('technician_signed_at',$u->createNamedParameter($now))
   ->set('status',$u->createNamedParameter('Unterschrieben'))->set('locked',$u->createNamedParameter(1))
   ->set('finalized_at',$u->createNamedParameter($now))->set('updated_at',$u->createNamedParameter($now))
   ->where($u->expr()->eq('id',$u->createNamedParameter($reportId)))->executeStatement();
  $this->writeMobileReportPdf($uid,$reportId);
  return ['id'=>$reportId,'status'=>'Unterschrieben','signedAt'=>$now];
 }

 private function mobileReportRow(int $id):array{
  $q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_reports')->where($q->expr()->eq('id',$q->createNamedParameter($id)));
  $row=$q->executeQuery()->fetchAssociative();if(!$row)throw new \InvalidArgumentException('Rapport nicht gefunden.');return $row;
 }

 private function writeMobileReportPdf(string $uid,int $reportId):void{
  $report=$this->mobileReportRow($reportId);$project=$this->projectRow((int)$report['project_id']);
  $cq=$this->db->getQueryBuilder();$cq->select('*')->from('re_erp_customers')->where($cq->expr()->eq('id',$cq->createNamedParameter((int)$report['customer_id'])));$customer=$cq->executeQuery()->fetchAssociative()?:null;
  $hq=$this->db->getQueryBuilder();$hq->select('*')->from('re_erp_report_hours')->where($hq->expr()->eq('report_id',$hq->createNamedParameter($reportId)))->orderBy('work_date','ASC');$hours=$hq->executeQuery()->fetchAllAssociative();
  $iq=$this->db->getQueryBuilder();$iq->select('*')->from('re_erp_report_items')->where($iq->expr()->eq('report_id',$iq->createNamedParameter($reportId)))->orderBy('id','ASC');$items=$iq->executeQuery()->fetchAllAssociative();
  $photos=[];$fq=$this->db->getQueryBuilder();$fq->select('*')->from('re_erp_report_files')->where($fq->expr()->eq('report_id',$fq->createNamedParameter($reportId)))->orderBy('id','ASC');
  foreach($fq->executeQuery()->fetchAllAssociative() as $file){$path=trim((string)($file['path']??''),'/');if($path==='')continue;try{$node=$this->rootFolder->getUserFolder($uid);foreach(explode('/',$path) as $part){if($part!=='')$node=$node->get($part);}if(!$node instanceof File)continue;$photos[]=['content'=>$node->getContent(),'mime'=>$node->getMimeType(),'path'=>$path,'name'=>$node->getName(),'created_at'=>(string)($file['created_at']??'')];}catch(\Throwable){}}
  $updated=$this->mobileReportRow($reportId);
  $pdf=$this->pdf->createReport($updated,$project,$customer,$hours,$items,null,$photos);
  $folder=trim((string)($updated['folder_path']??''),'/');if($folder==='')$folder=trim((string)($project['folder_path']??''),'/').'/Rapporte';
  $number=(string)($updated['report_no']??$reportId);$name='Rapport_'.preg_replace('/[^A-Za-z0-9._-]+/','_',$number).'.pdf';
  $this->folders->write($folder,$name,$pdf);
 }

 public function projectTimes(string $uid,int $projectId):array{
  $this->assertProjectAccess($uid,$projectId);
  $q=$this->db->getQueryBuilder();
  $q->select('e.id','e.activity','e.hours','e.imported_to_report_id','w.work_date','w.user_id')
   ->from('re_erp_workday_entries','e')
   ->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))
   ->where($q->expr()->eq('e.project_id',$q->createNamedParameter($projectId)))
   ->andWhere($q->expr()->isNull('e.imported_to_report_id'))
   ->orderBy('w.work_date','DESC')->addOrderBy('e.id','DESC')->setMaxResults(100);
  $rows=$q->executeQuery()->fetchAllAssociative();
  return array_map(function(array $row):array{
   $mq=$this->db->getQueryBuilder();$mq->select($mq->func()->count('*','c'),$mq->func()->sum('total_price','s'))->from('re_erp_workday_materials')->where($mq->expr()->eq('workday_entry_id',$mq->createNamedParameter((int)$row['id'])))->andWhere($mq->expr()->isNull('imported_to_report_id'));
   $material=$mq->executeQuery()->fetchNumeric();
   $user=$this->users->get((string)$row['user_id']);
   return ['id'=>(int)$row['id'],'workDate'=>(string)$row['work_date'],'userId'=>(string)$row['user_id'],'displayName'=>$user instanceof IUser?$user->getDisplayName():(string)$row['user_id'],'hours'=>(float)$row['hours'],'activity'=>(string)$row['activity'],'materialCount'=>(int)($material[0]??0),'materialTotal'=>(float)($material[1]??0)];
  },$rows);
 }
 public function createReport(string $uid,array $data):array{
  $projectId=(int)($data['projectId']??0);$this->assertProjectAccess($uid,$projectId);$project=$this->projectRow($projectId);
  $title=trim((string)($data['title']??'Arbeitsrapport'));if($title==='')throw new \InvalidArgumentException('Titel fehlt.');
  $reportDate=(string)($data['reportDate']??date('Y-m-d'));$now=date('Y-m-d H:i:s');$no=$this->numbers->next('report');$invoiceReady=(bool)($data['invoiceReady']??false);
  $customerSignature=$this->mobileSignature((string)($data['customerSignatureData']??''),'Kundenunterschrift');
  $technicianSignature=$this->mobileSignature((string)($data['technicianSignatureData']??''),'Monteurunterschrift');
  $customerSignedBy=trim((string)($data['customerSignedBy']??''));
  $technicianSignedBy=trim((string)($data['technicianSignedBy']??''));
  if($customerSignature!==null&&$customerSignedBy==='')throw new \InvalidArgumentException('Name des unterschreibenden Kunden fehlt.');
  if($technicianSignature!==null&&$technicianSignedBy==='')throw new \InvalidArgumentException('Name des Monteurs fehlt.');
  $signed=$customerSignature!==null;
  $status=$invoiceReady?'Für Rechnung':($signed?'Unterschrieben':'Entwurf');
  $qb=$this->db->getQueryBuilder();$qb->insert('re_erp_reports')->values([
   'customer_id'=>$qb->createNamedParameter((int)$project['customer_id']),
   'project_id'=>$qb->createNamedParameter($projectId),
   'report_no'=>$qb->createNamedParameter($no),
   'report_date'=>$qb->createNamedParameter($reportDate),
   'title'=>$qb->createNamedParameter($title),
   'description'=>$qb->createNamedParameter((string)($data['description']??'')),
   'customer_note'=>$qb->createNamedParameter((string)($data['customerNote']??'')),
   'status'=>$qb->createNamedParameter($status),
   'locked'=>$qb->createNamedParameter($signed?1:0),
   'signature_data'=>$qb->createNamedParameter($customerSignature),
   'signature_mime'=>$qb->createNamedParameter($customerSignature!==null?'image/png':null),
   'signed_by'=>$qb->createNamedParameter($customerSignature!==null?$customerSignedBy:null),
   'signed_at'=>$qb->createNamedParameter($customerSignature!==null?$now:null),
   'finalized_at'=>$qb->createNamedParameter($customerSignature!==null?$now:null),
   'technician_signature_data'=>$qb->createNamedParameter($technicianSignature),
   'technician_signature_mime'=>$qb->createNamedParameter($technicianSignature!==null?'image/png':null),
   'technician_signed_by'=>$qb->createNamedParameter($technicianSignature!==null?$technicianSignedBy:null),
   'technician_signed_at'=>$qb->createNamedParameter($technicianSignature!==null?$now:null),
   'created_by'=>$qb->createNamedParameter($uid),
   'created_at'=>$qb->createNamedParameter($now),
   'updated_at'=>$qb->createNamedParameter($now)
  ])->executeStatement();$reportId=(int)$this->db->lastInsertId('*PREFIX*re_erp_reports');
  $hourCount=0;$itemCount=0;$importCount=0;
  foreach((array)($data['hours']??[]) as $hour){if(!is_array($hour))continue;$hours=(float)($hour['hours']??0);if($hours<=0)continue;$h=$this->db->getQueryBuilder();$h->insert('re_erp_report_hours')->values(['report_id'=>$h->createNamedParameter($reportId),'user_id'=>$h->createNamedParameter($uid),'hours'=>$h->createNamedParameter($hours),'activity'=>$h->createNamedParameter(trim((string)($hour['activity']??'Arbeit'))?:'Arbeit'),'work_date'=>$h->createNamedParameter((string)($hour['workDate']??$reportDate)),'source_entry_id'=>$h->createNamedParameter(null)])->executeStatement();$hourCount++;}
  foreach((array)($data['materials']??[]) as $position){if(!is_array($position))continue;$materialId=(int)($position['materialId']??0);$quantity=(float)($position['quantity']??0);if($quantity<=0)continue;$material=null;if($materialId>0){$mq=$this->db->getQueryBuilder();$mq->select('*')->from('re_erp_materials')->where($mq->expr()->eq('id',$mq->createNamedParameter($materialId)));$material=$mq->executeQuery()->fetchAssociative()?:null;}$description=trim((string)($position['description']??($material['name']??'Material')));$unit=trim((string)($position['unit']??($material['unit']??'Stk.')));$price=array_key_exists('unitPrice',$position)?(float)$position['unitPrice']:(float)($material['sale_price']??$material['price']??0);$i=$this->db->getQueryBuilder();$i->insert('re_erp_report_items')->values(['report_id'=>$i->createNamedParameter($reportId),'material_id'=>$i->createNamedParameter($materialId>0?$materialId:null),'description'=>$i->createNamedParameter($description?:'Material'),'quantity'=>$i->createNamedParameter($quantity),'unit'=>$i->createNamedParameter($unit?:'Stk.'),'notes'=>$i->createNamedParameter('Direkt im mobilen Rapport erfasst'),'unit_price'=>$i->createNamedParameter($price),'total_price'=>$i->createNamedParameter(round($quantity*$price,2)),'source_workday_material_id'=>$i->createNamedParameter(null)])->executeStatement();$itemCount++;}
  foreach((array)($data['importEntryIds']??[]) as $raw){$entryId=(int)$raw;if($entryId<=0)continue;$eq=$this->db->getQueryBuilder();$eq->select('e.*','w.user_id','w.work_date')->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$eq->expr()->eq('w.id','e.workday_id'))->where($eq->expr()->eq('e.id',$eq->createNamedParameter($entryId)))->andWhere($eq->expr()->eq('e.project_id',$eq->createNamedParameter($projectId)))->andWhere($eq->expr()->isNull('e.imported_to_report_id'));$entry=$eq->executeQuery()->fetchAssociative();if(!$entry)continue;$h=$this->db->getQueryBuilder();$h->insert('re_erp_report_hours')->values(['report_id'=>$h->createNamedParameter($reportId),'user_id'=>$h->createNamedParameter((string)$entry['user_id']),'hours'=>$h->createNamedParameter((float)$entry['hours']),'activity'=>$h->createNamedParameter((string)$entry['activity']),'work_date'=>$h->createNamedParameter((string)$entry['work_date']),'source_entry_id'=>$h->createNamedParameter($entryId)])->executeStatement();$hourCount++;$importCount++;
   $mq=$this->db->getQueryBuilder();$mq->select('*')->from('re_erp_workday_materials')->where($mq->expr()->eq('workday_entry_id',$mq->createNamedParameter($entryId)))->andWhere($mq->expr()->isNull('imported_to_report_id'));foreach($mq->executeQuery()->fetchAllAssociative() as $mat){$i=$this->db->getQueryBuilder();$i->insert('re_erp_report_items')->values(['report_id'=>$i->createNamedParameter($reportId),'material_id'=>$i->createNamedParameter($mat['material_id']?:null),'description'=>$i->createNamedParameter((string)$mat['description']),'quantity'=>$i->createNamedParameter((float)$mat['quantity']),'unit'=>$i->createNamedParameter((string)$mat['unit']),'notes'=>$i->createNamedParameter('Aus Zeiterfassung übernommen'),'unit_price'=>$i->createNamedParameter((float)$mat['unit_price']),'total_price'=>$i->createNamedParameter((float)$mat['total_price']),'source_workday_material_id'=>$i->createNamedParameter((int)$mat['id'])])->executeStatement();$upm=$this->db->getQueryBuilder();$upm->update('re_erp_workday_materials')->set('imported_to_report_id',$upm->createNamedParameter($reportId))->where($upm->expr()->eq('id',$upm->createNamedParameter((int)$mat['id'])))->executeStatement();$itemCount++;}
   $up=$this->db->getQueryBuilder();$up->update('re_erp_workday_entries')->set('imported_to_report_id',$up->createNamedParameter($reportId));if($invoiceReady)$up->set('billing_status',$up->createNamedParameter('reserved'));$up->where($up->expr()->eq('id',$up->createNamedParameter($entryId)))->executeStatement();
  }
  if($hourCount===0){$d=$this->db->getQueryBuilder();$d->delete('re_erp_reports')->where($d->expr()->eq('id',$d->createNamedParameter($reportId)))->executeStatement();throw new \InvalidArgumentException('Mindestens eine Zeit ist erforderlich.');}
  $photoCount=0;
  foreach((array)($data['photoDocumentIds']??[]) as $rawPhotoId){
   $photoId=(int)$rawPhotoId;if($photoId<=0)continue;
   $pq=$this->db->getQueryBuilder();
   $pq->select('file_path','mime_type','document_type')->from('re_erp_project_documents')
    ->where($pq->expr()->eq('id',$pq->createNamedParameter($photoId)))
    ->andWhere($pq->expr()->eq('project_id',$pq->createNamedParameter($projectId)));
   $photo=$pq->executeQuery()->fetchAssociative();
   if(!$photo)continue;
   $mime=(string)($photo['mime_type']??'');
   $documentType=(string)($photo['document_type']??'');
   if(!str_starts_with($mime,'image/')&&$documentType!=='photo')continue;
   $rf=$this->db->getQueryBuilder();
   $rf->insert('re_erp_report_files')->values([
    'report_id'=>$rf->createNamedParameter($reportId),
    'path'=>$rf->createNamedParameter((string)$photo['file_path']),
    'created_at'=>$rf->createNamedParameter($now),
   ])->executeStatement();
   $photoCount++;
  }
  return ['id'=>$reportId,'reportNo'=>$no,'status'=>$status,'hourCount'=>$hourCount,'materialCount'=>$itemCount,'photoCount'=>$photoCount,'importedTimeCount'=>$importCount,'invoiceReady'=>$invoiceReady,'customerSigned'=>$customerSignature!==null,'technicianSigned'=>$technicianSignature!==null];
 }
 private function mobileSignature(string $data,string $label):?string{
  $data=trim($data);
  if($data==='')return null;
  if(!str_starts_with($data,'data:image/png;base64,'))throw new \InvalidArgumentException($label.' ist ungültig.');
  $encoded=substr($data,strpos($data,',')+1);
  if(strlen($encoded)>4000000)throw new \InvalidArgumentException($label.' ist zu groß.');
  $raw=base64_decode($encoded,true);
  if($raw===false||strlen($raw)<100||substr($raw,0,8)!=="\x89PNG\r\n\x1a\n")throw new \InvalidArgumentException($label.' konnte nicht verarbeitet werden.');
  return $data;
 }
 public function createTime(string $uid,array $data):array{
  $projectId=(int)($data['projectId']??0);
  $this->assertProjectAccess($uid,$projectId);
  $date=(string)($data['workDate']??date('Y-m-d'));
  $hours=(float)($data['hours']??0);
  if($hours<=0)throw new \InvalidArgumentException('Stunden müssen größer als 0 sein.');
  $activity=trim((string)($data['activity']??'Arbeit'));
  $note=trim((string)($data['note']??''));
  if($note!=='')$activity=mb_substr($activity.' · '.$note,0,255);
  $materials=is_array($data['materials']??null)?$data['materials']:[];

  $qb=$this->db->getQueryBuilder();
  $qb->select('id')->from('re_erp_workdays')
   ->where($qb->expr()->eq('user_id',$qb->createNamedParameter($uid)))
   ->andWhere($qb->expr()->eq('work_date',$qb->createNamedParameter($date)));
  $workdayId=$qb->executeQuery()->fetchOne();
  if(!$workdayId){
   $q=$this->db->getQueryBuilder();
   $q->insert('re_erp_workdays')->values([
    'user_id'=>$q->createNamedParameter($uid),
    'work_date'=>$q->createNamedParameter($date),
    'break_minutes'=>$q->createNamedParameter(0),
    'created_at'=>$q->createNamedParameter(date('Y-m-d H:i:s')),
   ])->executeStatement();
   $workdayId=(int)$this->db->lastInsertId('*PREFIX*re_erp_workdays');
  }

  $p=$this->projectRow($projectId);
  $q=$this->db->getQueryBuilder();
  $q->insert('re_erp_workday_entries')->values([
   'workday_id'=>$q->createNamedParameter((int)$workdayId),
   'customer_id'=>$q->createNamedParameter((int)$p['customer_id']),
   'project_id'=>$q->createNamedParameter($projectId),
   'activity'=>$q->createNamedParameter($activity!==''?$activity:'Arbeit'),
   'hours'=>$q->createNamedParameter($hours),
  ])->executeStatement();
  $entryId=(int)$this->db->lastInsertId('*PREFIX*re_erp_workday_entries');

  $savedMaterials=[];
  $materialTotal=0.0;
  foreach($materials as $position){
   if(!is_array($position))continue;
   $materialId=(int)($position['materialId']??0);
   $quantity=(float)($position['quantity']??0);
   if($materialId<=0||$quantity<=0)continue;

   $m=$this->db->getQueryBuilder();
   $m->select('id','name','article_no','unit','sale_price')->from('re_erp_materials')
    ->where($m->expr()->eq('id',$m->createNamedParameter($materialId)))
    ->andWhere($m->expr()->eq('active',$m->createNamedParameter(1)));
   $material=$m->executeQuery()->fetchAssociative();
   if(!$material)throw new \InvalidArgumentException('Material nicht gefunden: '.$materialId);

   $description=trim((string)($position['description']??$material['name']??'Material'));
   $unit=trim((string)($position['unit']??$material['unit']??'Stk.'));
   $unitPrice=array_key_exists('unitPrice',$position)?(float)$position['unitPrice']:(float)($material['sale_price']??0);
   $total=round($quantity*$unitPrice,2);

   $i=$this->db->getQueryBuilder();
   $i->insert('re_erp_workday_materials')->values([
    'workday_entry_id'=>$i->createNamedParameter($entryId),
    'timer_id'=>$i->createNamedParameter(null),
    'material_id'=>$i->createNamedParameter($materialId),
    'description'=>$i->createNamedParameter($description!==''?$description:(string)$material['name']),
    'quantity'=>$i->createNamedParameter($quantity),
    'unit'=>$i->createNamedParameter($unit!==''?$unit:'Stk.'),
    'unit_price'=>$i->createNamedParameter($unitPrice),
    'total_price'=>$i->createNamedParameter($total),
    'imported_to_report_id'=>$i->createNamedParameter(null),
    'invoice_id'=>$i->createNamedParameter(null),
    'created_by'=>$i->createNamedParameter($uid),
    'created_at'=>$i->createNamedParameter(date('Y-m-d H:i:s')),
   ])->executeStatement();
   $savedMaterials[]=[
    'id'=>(int)$this->db->lastInsertId('*PREFIX*re_erp_workday_materials'),
    'materialId'=>$materialId,
    'articleNo'=>(string)($material['article_no']??''),
    'description'=>$description,
    'quantity'=>$quantity,
    'unit'=>$unit,
    'unitPrice'=>$unitPrice,
    'totalPrice'=>$total,
   ];
   $materialTotal+=$total;
  }

  return [
   'id'=>$entryId,
   'hours'=>$hours,
   'workDate'=>$date,
   'materials'=>$savedMaterials,
   'materialCount'=>count($savedMaterials),
   'materialTotal'=>round($materialTotal,2),
  ];
 }
 public function upload(string $uid,array $file,int $projectId,string $type='document',string $category='Sonstige'):array{
  $this->assertProjectAccess($uid,$projectId);
  if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new \InvalidArgumentException('Keine gültige Datei empfangen.');
  if((int)($file['size']??0)>100*1024*1024)throw new \InvalidArgumentException('Datei ist größer als 100 MB.');
  $project=$this->projectRow($projectId);
  $base=trim((string)($project['folder_path']??''),'/');
  if($base==='')throw new \RuntimeException('Projektordner fehlt.');
  $allowedCategories=['Vorher','Nachher','Montage','Schaden','Abnahme','Sonstige'];
  $category=trim($category);
  if(!in_array($category,$allowedCategories,true))$category='Sonstige';
  $folderMap=['photo'=>'07_Fotos/'.$category,'scan'=>'00_Eingang','pdf'=>'12_Sonstiges','document'=>'12_Sonstiges'];
  $sub=$folderMap[$type]??'12_Sonstiges';
  $target=$this->ensureFolder($uid,$base.'/'.$sub);
  $name=$this->safeFile((string)($file['name']??'Datei'));
  if($type==='photo'){
   $extension=pathinfo($name,PATHINFO_EXTENSION);
   $stem=pathinfo($name,PATHINFO_FILENAME);
   $name=$this->safeFile(date('Y-m-d_H-i-s').'_'.$category.'_'.$stem.($extension!==''?'.'.$extension:''));
  }
  $content=file_get_contents((string)$file['tmp_name']);
  if($content===false)throw new \RuntimeException('Upload konnte nicht gelesen werden.');
  $node=$target->newFile($name,$content);
  $path=$base.'/'.$sub.'/'.$name;
  $q=$this->db->getQueryBuilder();
  $q->insert('re_erp_project_documents')->values([
   'project_id'=>$q->createNamedParameter($projectId),
   'customer_id'=>$q->createNamedParameter((int)$project['customer_id']),
   'document_type'=>$q->createNamedParameter($type==='photo'?'photo':'other'),
   'file_name'=>$q->createNamedParameter($name),
   'file_path'=>$q->createNamedParameter($path),
   'mime_type'=>$q->createNamedParameter($node->getMimeType()),
   'status'=>$q->createNamedParameter($type==='photo'?'photo_'.$category:'uploaded'),
   'source'=>$q->createNamedParameter('mobile'),
   'created_by'=>$q->createNamedParameter($uid),
   'created_at'=>$q->createNamedParameter(date('Y-m-d H:i:s')),
  ])->executeStatement();
  return [
   'id'=>(int)$this->db->lastInsertId('*PREFIX*re_erp_project_documents'),
   'fileId'=>$node->getId(),
   'name'=>$name,
   'path'=>$path,
   'mime'=>$node->getMimeType(),
   'category'=>$type==='photo'?$category:null,
  ];
 }
 public function sync(string $uid,array $changes):array{$results=[];foreach($changes as $change){try{$uuid=(string)($change['uuid']??'');$type=(string)($change['type']??'');$payload=(array)($change['payload']??[]);$data=match($type){'time'=>$this->createTime($uid,$payload),'report'=>$this->createReport($uid,$payload),default=>throw new \InvalidArgumentException('Unbekannter Sync-Typ: '.$type)};$results[]=['uuid'=>$uuid,'success'=>true,'data'=>$data];}catch(\Throwable $e){$results[]=['uuid'=>(string)($change['uuid']??''),'success'=>false,'error'=>$e->getMessage()];}}return ['results'=>$results,'serverTime'=>date(DATE_ATOM)];}
 private function issueTokens(IUser $user,?string $deviceName):array{$access=bin2hex(random_bytes(32));$refresh=bin2hex(random_bytes(48));$now=time();$qb=$this->db->getQueryBuilder();$qb->insert('re_erp_mobile_tokens')->values(['user_id'=>$qb->createNamedParameter($user->getUID()),'token_hash'=>$qb->createNamedParameter(hash('sha256',$access)),'refresh_hash'=>$qb->createNamedParameter(hash('sha256',$refresh)),'device_name'=>$qb->createNamedParameter($deviceName),'expires_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s',$now+self::ACCESS_TTL)),'refresh_expires_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s',$now+self::REFRESH_TTL)),'created_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s',$now))])->executeStatement();return ['user'=>$this->userPayload($user),'role'=>$this->role($user->getUID()),'permissions'=>$this->permissions($user->getUID()),'accessToken'=>$access,'refreshToken'=>$refresh,'expiresIn'=>self::ACCESS_TTL,'serverVersion'=>$this->version(),'apiVersion'=>1];}
 private function revokeById(int $id):void{$q=$this->db->getQueryBuilder();$q->update('re_erp_mobile_tokens')->set('revoked_at',$q->createNamedParameter(date('Y-m-d H:i:s')))->where($q->expr()->eq('id',$q->createNamedParameter($id)))->executeStatement();}
 private function userPayload(IUser $u):array{return ['id'=>$u->getUID(),'displayName'=>$u->getDisplayName(),'username'=>$u->getUID()];}
 private function requiredUser(string $uid):IUser{$u=$this->users->get($uid);if(!$u instanceof IUser)throw new \RuntimeException('Benutzer nicht gefunden.');return $u;}
 private function role(string $uid):string{if($this->groups->isAdmin($uid))return 'administrator';$q=$this->db->getQueryBuilder();$q->select('role')->from('re_erp_user_roles')->where($q->expr()->eq('user_id',$q->createNamedParameter($uid)));$r=$q->executeQuery()->fetchOne();return is_string($r)&&$r!==''?$r:'employee';}
 private function permissions(string $uid):array{$role=$this->role($uid);return match($role){'administrator','admin'=>['*'],'office'=>['dashboard.read','projects.read','projects.write','documents.read','documents.upload','reports.read','reports.write','time.read','time.write','materials.read'],'manager'=>['dashboard.read','projects.read','projects.write','documents.read','documents.upload','reports.read','reports.write','time.read','time.write','materials.read'],'employee'=>['dashboard.read','projects.read','documents.read','documents.upload','reports.read','reports.write','time.write','materials.read'],default=>['projects.read','time.write']};}
 private function version():string{return (string)$this->config->getAppValue('reinhardterp','installed_version','0.66.0');}
 private function simpleList(string $table,string $id,string $label,string $active):array{$q=$this->db->getQueryBuilder();$q->select($id,$label)->from($table)->where($q->expr()->eq($active,$q->createNamedParameter(1)))->orderBy($label,'ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function countWhere(string $table,string $column,mixed $value):int{$q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','c'))->from($table)->where($q->expr()->eq($column,$q->createNamedParameter($value)));return (int)$q->executeQuery()->fetchOne();}
 private function countProjectsToday(string $uid,string $today):int{$projects=$this->projects($uid,250);return count(array_filter($projects,static fn(array $p):bool=>(string)($p['startDate']??'')<=$today&&((string)($p['dueDate']??'')===''||(string)$p['dueDate']>=$today)));}
 private function countFutureEvents(string $today):int{$q=$this->db->getQueryBuilder();$q->select($q->func()->count('*','c'))->from('re_erp_team_events')->where($q->expr()->gte('start_at',$q->createNamedParameter($today.' 00:00:00')))->andWhere($q->expr()->eq('is_deleted',$q->createNamedParameter(0)));return (int)$q->executeQuery()->fetchOne();}
 private function todayHours(string $uid,string $today):float{$q=$this->db->getQueryBuilder();$q->select($q->func()->sum('e.hours','s'))->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->where($q->expr()->eq('w.user_id',$q->createNamedParameter($uid)))->andWhere($q->expr()->eq('w.work_date',$q->createNamedParameter($today)));return (float)($q->executeQuery()->fetchOne()?:0);}
 private function appointments(string $today,int $limit):array{$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_team_events')->where($q->expr()->gte('start_at',$q->createNamedParameter($today.' 00:00:00')))->andWhere($q->expr()->eq('is_deleted',$q->createNamedParameter(0)))->orderBy('start_at','ASC')->setMaxResults($limit);return $q->executeQuery()->fetchAllAssociative();}
 private function projectSummary(array $r):array{$address=trim(implode(' ',array_filter([(string)($r['street']??''),(string)($r['postal_code']??''),(string)($r['city']??'')])));return ['id'=>(int)$r['id'],'projectNo'=>(string)$r['project_no'],'projectName'=>(string)$r['title'],'customer'=>(string)($r['customer_name']??''),'customerId'=>(int)$r['customer_id'],'status'=>(string)$r['status'],'startDate'=>$r['start_date']??null,'dueDate'=>$r['due_date']??null,'address'=>$address,'contactName'=>(string)($r['contact_name']??''),'phone'=>(string)($r['customer_mobile']??$r['customer_phone']??$r['mobile']??$r['phone']??''),'email'=>(string)($r['customer_email']??$r['email']??''),'color'=>$this->statusColor((string)$r['status']),'progress'=>$this->statusProgress((string)$r['status'])];}
 private function statusColor(string $s):string{return match(strtolower($s)){'anfrage'=>'#607d8b','angebot'=>'#1976d2','auftrag'=>'#5e35b1','fertigung'=>'#fb8c00','montage'=>'#00897b','abnahme'=>'#43a047','abrechnung'=>'#7cb342','abgeschlossen'=>'#2e7d32',default=>'#546e7a'};}
 private function statusProgress(string $s):int{return match(strtolower($s)){'anfrage'=>10,'angebot'=>20,'auftrag'=>35,'fertigung'=>55,'montage'=>75,'abnahme'=>85,'abrechnung'=>95,'abgeschlossen'=>100,default=>5};}
 private function assertProjectAccess(string $uid,int $id):void{if($id<=0)throw new \InvalidArgumentException('Projekt fehlt.');$role=$this->role($uid);if(in_array($role,['administrator','admin','office','manager'],true)){if(!$this->projectRow($id))throw new \RuntimeException('Projekt nicht gefunden.');return;}$q=$this->db->getQueryBuilder();$q->select('id')->from('re_erp_project_users')->where($q->expr()->eq('project_id',$q->createNamedParameter($id)))->andWhere($q->expr()->eq('user_id',$q->createNamedParameter($uid)));if(!$q->executeQuery()->fetchOne())throw new \RuntimeException('Keine Berechtigung für dieses Projekt.');}
 private function projectRow(int $id):array{$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_projects')->where($q->expr()->eq('id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();if(!$r)throw new \RuntimeException('Projekt nicht gefunden.');return $r;}
 private function mobileDocumentRow(array $row,int $projectId):array{
  $path=trim((string)($row['file_path']??$row['path']??''),'/');
  $name=(string)($row['file_name']??$row['name']??basename($path));
  $mime=(string)($row['mime_type']??$row['mime']??'application/octet-stream');
  $mtime=(int)($row['mtime']??0);
  $created=(string)($row['created_at']??'');
  if($created===''&&$mtime>0)$created=date('Y-m-d H:i:s',$mtime);
  return [
   'id'=>(int)($row['id']??0),
   'project_id'=>$projectId,
   'document_type'=>(string)($row['document_type']??$this->documentTypeFromPath($path,$mime)),
   'file_name'=>$name,
   'file_path'=>$path,
   'mime_type'=>$mime,
   'status'=>(string)($row['status']??'available'),
   'source'=>(string)($row['source']??'nextcloud'),
   'created_by'=>(string)($row['created_by']??''),
   'created_at'=>$created,
   'size'=>(int)($row['size']??0),
   'mtime'=>$mtime,
  ];
 }
 private function documentTypeFromPath(string $path,string $mime):string{
  if(str_starts_with($mime,'image/'))return 'photo';
  $path='/'.strtolower($path).'/';
  return match(true){
   str_contains($path,'/00_eingang/')=>'inbox',
   str_contains($path,'/01_aufmass/')=>'measurement',
   str_contains($path,'/02_planung/')=>'planning',
   str_contains($path,'/03_zeichnungen/')=>'drawing',
   str_contains($path,'/04_material/')=>'material',
   str_contains($path,'/05_bestellungen/')=>'purchase',
   str_contains($path,'/06_rapporte/')=>'report',
   str_contains($path,'/07_fotos/')=>'photo',
   str_contains($path,'/08_abnahme/')=>'acceptance',
   str_contains($path,'/09_rechnung/')=>'invoice',
   str_contains($path,'/10_angebote/')=>'offer',
   str_contains($path,'/11_auftraege/')=>'order',
   default=>'other',
  };
 }
 private function existingFolder(string $uid,string $path):Folder{
  $node=$this->rootFolder->getUserFolder($uid);
  foreach(explode('/',trim($path,'/')) as $part){
   if($part==='')continue;
   $child=$node->get($part);
   if(!$child instanceof Folder)throw new \RuntimeException('Projektpfad ist kein Ordner.');
   $node=$child;
  }
  return $node;
 }
 private function collectProjectFiles(Folder $folder,string $path,int $depth,int $maxDepth,array &$rows,int $limit):void{
  if(count($rows)>=$limit)return;
  foreach($folder->getDirectoryListing() as $node){
   if(count($rows)>=$limit)return;
   $nodePath=trim($path.'/'.$node->getName(),'/');
   if($node instanceof File){
    $rows[]=[
     'id'=>$node->getId(),
     'file_name'=>$node->getName(),
     'file_path'=>$nodePath,
     'mime_type'=>$node->getMimeType(),
     'size'=>$node->getSize(),
     'mtime'=>$node->getMTime(),
     'status'=>'available',
     'source'=>'nextcloud',
    ];
   }elseif($node instanceof Folder&&$depth<$maxDepth){
    $this->collectProjectFiles($node,$nodePath,$depth+1,$maxDepth,$rows,$limit);
   }
  }
 }
 private function projectMaterial(int $id):array{$q=$this->db->getQueryBuilder();$q->select('i.description','i.quantity','i.unit','i.unit_price','r.report_no','r.report_date')->from('re_erp_report_items','i')->innerJoin('i','re_erp_reports','r',$q->expr()->eq('r.id','i.report_id'))->where($q->expr()->eq('r.project_id',$q->createNamedParameter($id)))->orderBy('r.report_date','DESC')->setMaxResults(100);return $q->executeQuery()->fetchAllAssociative();}
 private function projectEvents(int $id):array{$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_team_events')->where($q->expr()->eq('project_id',$q->createNamedParameter($id)))->andWhere($q->expr()->eq('is_deleted',$q->createNamedParameter(0)))->orderBy('start_at','ASC')->setMaxResults(50);return $q->executeQuery()->fetchAllAssociative();}
 private function projectReports(int $id):array{$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_reports')->where($q->expr()->eq('project_id',$q->createNamedParameter($id)))->andWhere($q->expr()->eq('archived',$q->createNamedParameter(0)))->orderBy('report_date','DESC')->setMaxResults(50);return $q->executeQuery()->fetchAllAssociative();}
 private function ensureFolder(string $uid,string $path):Folder{$folder=$this->rootFolder->getUserFolder($uid);foreach(explode('/',trim($path,'/')) as $part){if($part==='')continue;try{$node=$folder->get($part);if(!$node instanceof Folder)throw new \RuntimeException('Pfadbestandteil ist keine Mappe.');$folder=$node;}catch(\OCP\Files\NotFoundException){$folder=$folder->newFolder($part);}}return $folder;}
 private function safeFile(string $name):string{$name=preg_replace('/[^\pL\pN._ -]+/u','_',trim($name))??'Datei';return trim($name,'. ')?:'Datei';}
}
