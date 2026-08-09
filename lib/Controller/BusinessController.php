<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;
final class BusinessController extends Controller {
 public function __construct(string $appName,IRequest $request,private IDBConnection $db,private IURLGenerator $url,private IUserSession $session,private PermissionService $permissions,private NumberService $numbers){parent::__construct($appName,$request);}
 #[NoAdminRequired,NoCSRFRequired] public function crm():TemplateResponse{$this->permissions->assert('crm');return $this->page('crm',['communications'=>$this->communications(),'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no'),'dueFollowUps'=>$this->dueFollowUps()]);}
 #[NoAdminRequired] public function saveCommunication(int $customerId,string $type,string $subject,?string $details=null,?int $projectId=null,?string $contactAt=null,?string $followUpAt=null):RedirectResponse{$this->permissions->assert('crm');if(trim($subject)==='')throw new \InvalidArgumentException('Betreff fehlt.');$this->insert('re_erp_communications',['customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'type'=>$type,'subject'=>trim($subject),'details'=>$details,'contact_at'=>$this->dt($contactAt)??date('Y-m-d H:i:s'),'follow_up_at'=>$this->dt($followUpAt),'created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.crm');}
 #[NoAdminRequired,NoCSRFRequired] public function offers():TemplateResponse{$this->permissions->assert('offers');return $this->page('offers',['offers'=>$this->offersRows(),'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no')]);}
 #[NoAdminRequired] public function saveOffer(int $customerId,string $title,string $description,float $quantity=1,string $unit='Stk.',float $unitPrice=0,?int $projectId=null,?string $validUntil=null,float $vatRate=19,?string $notes=null):RedirectResponse{$this->permissions->assert('offers');$title=trim($title);if($title==='')throw new \InvalidArgumentException('Titel fehlt.');$qty=max(0.01,$quantity);$net=round($qty*$unitPrice,2);$gross=round($net*(1+$vatRate/100),2);$now=date('Y-m-d H:i:s');$id=$this->insert('re_erp_offers',['offer_no'=>$this->numbers->next('offer'),'customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'title'=>$title,'offer_date'=>date('Y-m-d'),'valid_until'=>$validUntil?:null,'status'=>'draft','notes'=>$notes,'net_amount'=>$net,'vat_rate'=>$vatRate,'gross_amount'=>$gross,'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now]);$this->insert('re_erp_offer_items',['offer_id'=>$id,'position_no'=>1,'description'=>trim($description)!==''?trim($description):$title,'quantity'=>$qty,'unit'=>$unit?:'Stk.','unit_price'=>$unitPrice,'total_price'=>$net]);return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);}
 #[NoAdminRequired,NoCSRFRequired] public function offerDetail(int $id):TemplateResponse{$this->permissions->assert('offers');$offer=$this->offer($id);return $this->page('offer_detail',['offer'=>$offer,'items'=>$this->where('re_erp_offer_items','offer_id',$id,'position_no')]);}
 #[NoAdminRequired] public function updateOfferStatus(int $id,string $status):RedirectResponse{$this->permissions->assert('offers');if(!in_array($status,['draft','sent','accepted','rejected','expired'],true))throw new \InvalidArgumentException('Ungültiger Status.');$this->update('re_erp_offers',$id,['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);}
 #[NoAdminRequired] public function createOrderFromOffer(int $id):RedirectResponse{$this->permissions->assert('orders');$offer=$this->offer($id);$existing=$this->oneBy('re_erp_orders','offer_id',$id);if($existing)return $this->go('reinhardterp.business.orderDetail',['id'=>$existing['id']]);$now=date('Y-m-d H:i:s');$orderId=$this->insert('re_erp_orders',['order_no'=>$this->numbers->next('order'),'offer_id'=>$id,'customer_id'=>$offer['customer_id'],'project_id'=>$offer['project_id'],'title'=>$offer['title'],'order_date'=>date('Y-m-d'),'status'=>'open','net_amount'=>$offer['net_amount'],'gross_amount'=>$offer['gross_amount'],'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now]);foreach($this->where('re_erp_offer_items','offer_id',$id,'position_no') as $i){$this->insert('re_erp_order_items',['order_id'=>$orderId,'position_no'=>$i['position_no'],'description'=>$i['description'],'quantity'=>$i['quantity'],'unit'=>$i['unit'],'unit_price'=>$i['unit_price'],'total_price'=>$i['total_price']]);}$this->update('re_erp_offers',$id,['status'=>'accepted','updated_at'=>$now]);return $this->go('reinhardterp.business.orderDetail',['id'=>$orderId]);}
 #[NoAdminRequired,NoCSRFRequired] public function orders():TemplateResponse{$this->permissions->assert('orders');return $this->page('orders',['orders'=>$this->ordersRows()]);}
 #[NoAdminRequired,NoCSRFRequired] public function orderDetail(int $id):TemplateResponse{$this->permissions->assert('orders');$order=$this->order($id);return $this->page('order_detail',['order'=>$order,'items'=>$this->where('re_erp_order_items','order_id',$id,'position_no')]);}
 #[NoAdminRequired] public function updateOrderStatus(int $id,string $status):RedirectResponse{$this->permissions->assert('orders');if(!in_array($status,['open','confirmed','production','installation','completed','cancelled'],true))throw new \InvalidArgumentException('Ungültiger Status.');$this->update('re_erp_orders',$id,['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.orderDetail',['id'=>$id]);}
 #[NoAdminRequired,NoCSRFRequired] public function inventory():TemplateResponse{$this->permissions->assert('inventory');return $this->page('inventory',['materials'=>$this->materialsWithMeta(),'movements'=>$this->stockRows(),'projects'=>$this->rows('re_erp_projects','project_no')]);}
 #[NoAdminRequired] public function saveStockMovement(int $materialId,string $movementType,float $quantity,?int $projectId=null,?string $note=null):RedirectResponse{$this->permissions->assert('inventory');if(!in_array($movementType,['in','out','adjustment'],true))throw new \InvalidArgumentException('Ungültige Lagerbewegung.');$m=$this->one('re_erp_materials',$materialId);if(!$m)throw new \InvalidArgumentException('Material nicht gefunden.');$qty=abs($quantity);$current=(float)($m['stock_quantity']??0);$new=$movementType==='in'?$current+$qty:($movementType==='out'?$current-$qty:$quantity);if($new<0)throw new \InvalidArgumentException('Lagerbestand darf nicht negativ werden.');$this->db->beginTransaction();try{$this->insert('re_erp_stock_movements',['material_id'=>$materialId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'movement_type'=>$movementType,'quantity'=>$movementType==='out'?-1*$qty:($movementType==='in'?$qty:$quantity),'note'=>$note,'created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);$this->update('re_erp_materials',$materialId,['stock_quantity'=>$new]);$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}return $this->go('reinhardterp.business.inventory');}
 #[NoAdminRequired,NoCSRFRequired] public function aboutRelease():TemplateResponse{
  $this->permissions->assert('settings');
  return $this->page('about_release',['urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function mobileAdmin():TemplateResponse{
  $this->permissions->assert('settings');
  return $this->page('mobile_admin',['urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function documentation():TemplateResponse{
  return $this->page('documentation',['urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function mobile():TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('mobile'); $uid=$this->uid();
  $projects=array_values(array_filter($this->rows('re_erp_projects','project_no'),fn(array $p):bool=>$this->permissions->canAccessProject((int)$p['id'],$uid)));
  $view=(string)$this->request->getParam('view','today');
  return $this->page('mobile',[
    'view'=>$view,
    'projects'=>$projects,
    'todayHours'=>$this->todayHours($uid),
    'recent'=>$this->recentEntries($uid),
    'displayName'=>$this->session->getUser()?->getDisplayName() ?: $uid,
    'role'=>$this->permissions->role(),
    'urlGenerator'=>$this->url,
  ]);
 }

 #[NoAdminRequired,NoCSRFRequired] public function mobileProject(int $id):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('mobile');$uid=$this->uid();$this->permissions->assertProjectAccess($id);
  $project=$this->one('re_erp_projects',$id);if(!$project)throw new \InvalidArgumentException('Projekt nicht gefunden.');
  $customer=!empty($project['customer_id'])?$this->one('re_erp_customers',(int)$project['customer_id']):null;
  return $this->page('mobile_project',['project'=>$project,'customer'=>$customer,'urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function mobileMaterial(?int $projectId=null,?string $q=null):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('materials_use');$uid=$this->uid();
  $projects=array_values(array_filter($this->rows('re_erp_projects','project_no'),fn(array $p):bool=>$this->permissions->canAccessProject((int)$p['id'],$uid)));
  if($projectId)$this->permissions->assertProjectAccess($projectId);
  $materials=$this->materialsWithMeta();$query=mb_strtolower(trim((string)$q));
  if($query!=='')$materials=array_values(array_filter($materials,static function(array $m)use($query):bool{$hay=mb_strtolower(implode(' ',[(string)($m['article_no']??''),(string)($m['name']??''),(string)($m['barcode']??''),(string)($m['storage_location']??'')]));return str_contains($hay,$query);}));
  return $this->page('mobile_material',['materials'=>$materials,'projects'=>$projects,'projectId'=>$projectId,'query'=>$q,'urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired] public function saveMobileMaterial(int $materialId,int $projectId,float $quantity,?string $note=null,?string $clientOperationId=null):RedirectResponse{
  $this->permissions->assert('materials_use');$this->permissions->assertProjectAccess($projectId);if($this->offlineOpDone($clientOperationId))return $this->go('reinhardterp.business.mobileMaterial',['projectId'=>$projectId]);
  if($quantity<=0)throw new \InvalidArgumentException('Menge muss größer als 0 sein.');$m=$this->one('re_erp_materials',$materialId);if(!$m)throw new \InvalidArgumentException('Material nicht gefunden.');
  $current=(float)($m['stock_quantity']??0);if($quantity>$current)throw new \InvalidArgumentException('Nicht genügend Lagerbestand vorhanden.');
  $this->db->beginTransaction();try{$this->insert('re_erp_stock_movements',['material_id'=>$materialId,'project_id'=>$projectId,'movement_type'=>'out','quantity'=>-1*abs($quantity),'note'=>trim((string)$note)?:'Mobile Materialentnahme','created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);$this->update('re_erp_materials',$materialId,['stock_quantity'=>$current-$quantity]);$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.mobileMaterial',['projectId'=>$projectId]);
 }

 #[NoAdminRequired,NoCSRFRequired] public function mobileTime(?int $projectId=null):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('time');$uid=$this->uid();
  $projects=array_values(array_filter($this->rows('re_erp_projects','project_no'),fn(array $p):bool=>$this->permissions->canAccessProject((int)$p['id'],$uid)));
  if($projectId){$this->permissions->assertProjectAccess($projectId);}
  return $this->page('mobile_time',['projects'=>$projects,'projectId'=>$projectId,'userId'=>$uid,'urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired] public function saveMobileTime(int $projectId,string $workDate,float $hours=0,string $activity='',?string $startTime=null,?string $endTime=null,int $breakMinutes=0,?string $notes=null,?string $clientOperationId=null):RedirectResponse{
  $this->permissions->assert('time');$this->permissions->assertProjectAccess($projectId);$uid=$this->uid();if($this->offlineOpDone($clientOperationId))return $this->go('reinhardterp.business.mobileProject',['id'=>$projectId]);
  if($hours<=0&&$startTime&&$endTime){$start=strtotime($workDate.' '.$startTime);$end=strtotime($workDate.' '.$endTime);if($end<$start)$end+=86400;$hours=max(0,(($end-$start)/3600)-($breakMinutes/60));}
  $hours=round($hours,2);if($hours<=0)throw new \InvalidArgumentException('Stunden oder Beginn und Ende müssen angegeben werden.');if(trim($activity)==='')throw new \InvalidArgumentException('Tätigkeit fehlt.');
  $project=$this->one('re_erp_projects',$projectId);if(!$project)throw new \InvalidArgumentException('Projekt nicht gefunden.');
  $now=date('Y-m-d H:i:s');$workdayId=$this->insert('re_erp_workdays',['user_id'=>$uid,'work_date'=>$workDate,'start_time'=>$startTime?:null,'end_time'=>$endTime?:null,'break_minutes'=>$breakMinutes,'notes'=>$notes,'entered_by'=>$uid,'updated_by'=>$uid,'created_at'=>$now,'updated_at'=>$now]);
  $this->insert('re_erp_workday_entries',['workday_id'=>$workdayId,'customer_id'=>$project['customer_id']??null,'project_id'=>$projectId,'activity'=>trim($activity),'hours'=>$hours,'imported_to_report_id'=>null,'created_at'=>$now]);$this->markOfflineOpDone($clientOperationId);
  return $this->go('reinhardterp.business.mobileProject',['id'=>$projectId]);
 }

 #[NoAdminRequired,NoCSRFRequired] public function mobileReports(int $projectId):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('reports');$this->permissions->assertProjectAccess($projectId);
  $project=$this->one('re_erp_projects',$projectId);if(!$project)throw new \InvalidArgumentException('Projekt nicht gefunden.');
  $rows=array_values(array_filter($this->where('re_erp_reports','project_id',$projectId,'report_date'),static fn(array $r):bool=>empty($r['archived'])));
  usort($rows,static fn(array $a,array $b):int=>strcmp((string)($b['report_date']??''),(string)($a['report_date']??'')));
  return $this->page('mobile_reports',['project'=>$project,'reports'=>$rows,'urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function mobileReport(int $id):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('reports');$report=$this->one('re_erp_reports',$id);if(!$report)throw new \InvalidArgumentException('Rapport nicht gefunden.');
  $this->permissions->assertProjectAccess((int)$report['project_id']);$project=$this->one('re_erp_projects',(int)$report['project_id']);
  $hours=$this->where('re_erp_report_hours','report_id',$id,'id');$items=$this->where('re_erp_report_items','report_id',$id,'id');
  return $this->page('mobile_report',['report'=>$report,'project'=>$project,'hours'=>$hours,'items'=>$items,'urlGenerator'=>$this->url]);
 }
 #[NoAdminRequired] public function saveMobileSignature(int $id,string $signedBy,string $signatureData):RedirectResponse{
  $this->permissions->assert('reports');$report=$this->one('re_erp_reports',$id);if(!$report)throw new \InvalidArgumentException('Rapport nicht gefunden.');
  $this->permissions->assertProjectAccess((int)$report['project_id']);$signedBy=trim($signedBy);if($signedBy==='')throw new \InvalidArgumentException('Bitte Namen eintragen.');
  if(!str_starts_with($signatureData,'data:image/png;base64,'))throw new \InvalidArgumentException('Ungültige Unterschrift.');
  $encoded=substr($signatureData,strpos($signatureData,',')+1);if(strlen($encoded)>4000000)throw new \InvalidArgumentException('Unterschrift zu groß.');
  $raw=base64_decode($encoded,true);if($raw===false||strlen($raw)<100||substr($raw,0,8)!=="\x89PNG\r\n\x1a\n")throw new \InvalidArgumentException('Unterschrift konnte nicht verarbeitet werden.');
  $now=date('Y-m-d H:i:s');$this->update('re_erp_reports',$id,['signature_data'=>$signatureData,'signature_mime'=>'image/png','signed_by'=>$signedBy,'signed_at'=>$now,'status'=>'Unterschrieben','locked'=>1,'finalized_at'=>$now,'updated_at'=>$now]);
  return $this->go('reinhardterp.business.mobileReport',['id'=>$id]);
 }
 private function addPwaHeaders():void{
  $manifest=$this->url->linkToRoute('reinhardterp.page.pwaManifest').'?v=127-force';
  $icon=$this->url->linkToRoute('reinhardterp.page.pwaIcon',['size'=>'192']);
  Util::addHeader('link',['rel'=>'manifest','href'=>$manifest]);
  Util::addHeader('meta',['name'=>'theme-color','content'=>'#1265d8']);
  Util::addHeader('meta',['name'=>'mobile-web-app-capable','content'=>'yes']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-capable','content'=>'yes']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-title','content'=>'NextERP']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-status-bar-style','content'=>'default']);
  Util::addHeader('link',['rel'=>'apple-touch-icon','href'=>$icon]);
 }
 private function offlineOpMarker(?string $clientOperationId):?string{
  $id=trim((string)$clientOperationId);if($id===''||!preg_match('/^[a-zA-Z0-9._:-]{12,120}$/',$id))return null;
  return rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'nexterp-offline-'.hash('sha256',$this->uid().'|'.$id).'.done';
 }
 private function offlineOpDone(?string $clientOperationId):bool{$f=$this->offlineOpMarker($clientOperationId);return $f!==null&&is_file($f);}
 private function markOfflineOpDone(?string $clientOperationId):void{$f=$this->offlineOpMarker($clientOperationId);if($f!==null)@file_put_contents($f,(string)time(),LOCK_EX);}
 private function page(string $template,array $data):TemplateResponse{return new TemplateResponse($this->appName,$template,$data);}
 private function go(string $route,array $params=[]):RedirectResponse{return new RedirectResponse($this->url->linkToRoute($route,$params));}
 private function uid():string{return $this->session->getUser()?->getUID()??'';}
 private function dt(?string $v):?string{if(!$v)return null;$t=strtotime($v);return $t===false?null:date('Y-m-d H:i:s',$t);}
 private function insert(string $table,array $data):int{$q=$this->db->getQueryBuilder();$q->insert($table);$vals=[];foreach($data as $k=>$v)$vals[$k]=$q->createNamedParameter($v);$q->values($vals)->executeStatement();return (int)$this->db->lastInsertId($table);}
 private function update(string $table,int $id,array $data):void{$q=$this->db->getQueryBuilder();$q->update($table);foreach($data as $k=>$v)$q->set($k,$q->createNamedParameter($v));$q->where($q->expr()->eq('id',$q->createNamedParameter($id)))->executeStatement();}
 private function one(string $table,int $id):?array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->where($q->expr()->eq('id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();return $r?:null;}
 private function oneBy(string $table,string $col,int $value):?array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->where($q->expr()->eq($col,$q->createNamedParameter($value)));$r=$q->executeQuery()->fetchAssociative();return $r?:null;}
 private function rows(string $table,string $order):array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->orderBy($order,'ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function where(string $table,string $col,int $value,string $order):array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->where($q->expr()->eq($col,$q->createNamedParameter($value)))->orderBy($order,'ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function communications():array{$q=$this->db->getQueryBuilder();$q->select('x.*','c.name AS customer_name','p.project_no','p.title AS project_title')->from('re_erp_communications','x')->leftJoin('x','re_erp_customers','c',$q->expr()->eq('c.id','x.customer_id'))->leftJoin('x','re_erp_projects','p',$q->expr()->eq('p.id','x.project_id'))->orderBy('x.contact_at','DESC')->setMaxResults(100);return $q->executeQuery()->fetchAllAssociative();}
 private function dueFollowUps():array{$q=$this->db->getQueryBuilder();$q->select('x.*','c.name AS customer_name')->from('re_erp_communications','x')->leftJoin('x','re_erp_customers','c',$q->expr()->eq('c.id','x.customer_id'))->where($q->expr()->isNotNull('x.follow_up_at'))->andWhere($q->expr()->lte('x.follow_up_at',$q->createNamedParameter(date('Y-m-d 23:59:59'))))->orderBy('x.follow_up_at','ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function offersRows():array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no')->from('re_erp_offers','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->orderBy('o.offer_date','DESC');return $q->executeQuery()->fetchAllAssociative();}
 private function offer(int $id):array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no','p.title AS project_title')->from('re_erp_offers','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->where($q->expr()->eq('o.id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();if(!$r)throw new \OCP\AppFramework\Http\NotFoundResponse();return $r;}
 private function ordersRows():array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no')->from('re_erp_orders','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->orderBy('o.order_date','DESC');return $q->executeQuery()->fetchAllAssociative();}
 private function order(int $id):array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no','p.title AS project_title')->from('re_erp_orders','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->where($q->expr()->eq('o.id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();if(!$r)throw new \OCP\AppFramework\Http\NotFoundResponse();return $r;}
 private function materialsWithMeta():array{$q=$this->db->getQueryBuilder();$q->select('m.*','g.name AS group_name','s.name AS supplier_name')->from('re_erp_materials','m')->leftJoin('m','re_erp_material_groups','g',$q->expr()->eq('g.id','m.material_group_id'))->leftJoin('m','re_erp_suppliers','s',$q->expr()->eq('s.id','m.supplier_id'))->orderBy('m.name','ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function stockRows():array{$q=$this->db->getQueryBuilder();$q->select('s.*','m.article_no','m.name AS material_name','m.unit','p.project_no')->from('re_erp_stock_movements','s')->leftJoin('s','re_erp_materials','m',$q->expr()->eq('m.id','s.material_id'))->leftJoin('s','re_erp_projects','p',$q->expr()->eq('p.id','s.project_id'))->orderBy('s.created_at','DESC')->setMaxResults(100);return $q->executeQuery()->fetchAllAssociative();}
 private function activeTimer(string $uid):?array{$q=$this->db->getQueryBuilder();$q->select('t.*','p.project_no','p.title')->from('re_erp_time_timers','t')->leftJoin('t','re_erp_projects','p',$q->expr()->eq('p.id','t.project_id'))->where($q->expr()->eq('t.user_id',$q->createNamedParameter($uid)))->andWhere($q->expr()->in('t.status',[$q->createNamedParameter('running'),$q->createNamedParameter('paused')]))->orderBy('t.id','DESC')->setMaxResults(1);$r=$q->executeQuery()->fetchAssociative();return $r?:null;}
 private function todayHours(string $uid):float{$q=$this->db->getQueryBuilder();$q->select($q->func()->sum('e.hours','s'))->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->where($q->expr()->eq('w.user_id',$q->createNamedParameter($uid)))->andWhere($q->expr()->eq('w.work_date',$q->createNamedParameter(date('Y-m-d'))));return (float)($q->executeQuery()->fetchOne()?:0);}
 private function recentEntries(string $uid):array{$q=$this->db->getQueryBuilder();$q->select('e.*','w.work_date','p.project_no','p.title')->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->leftJoin('e','re_erp_projects','p',$q->expr()->eq('p.id','e.project_id'))->where($q->expr()->eq('w.user_id',$q->createNamedParameter($uid)))->orderBy('w.work_date','DESC')->addOrderBy('e.id','DESC')->setMaxResults(10);return $q->executeQuery()->fetchAllAssociative();}
}
