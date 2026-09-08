<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\NumberService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\PdfService;
use OCA\ReinhardtERP\Service\XRechnungService;
use OCA\ReinhardtERP\Service\MailService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDBConnection;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;
final class BusinessController extends Controller {
 public function __construct(string $appName,IRequest $request,private IDBConnection $db,private IURLGenerator $url,private IUserSession $session,private PermissionService $permissions,private NumberService $numbers,private IConfig $config,private FolderService $folders,private PdfService $pdf,private XRechnungService $xrechnung,private MailService $mail){parent::__construct($appName,$request);}
 #[NoAdminRequired,NoCSRFRequired] public function crm():TemplateResponse{$this->permissions->assert('crm');$view=(string)$this->request->getParam('view','overview');if(!in_array($view,['overview','new','followups','history'],true)){$view='overview';}return $this->page('crm',['view'=>$view,'communications'=>$this->communications(),'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no'),'dueFollowUps'=>$this->dueFollowUps(),'urlGenerator'=>$this->url]);}
 #[NoAdminRequired] public function saveCommunication(int $customerId,string $type,string $subject,?string $details=null,?int $projectId=null,?string $contactAt=null,?string $followUpAt=null):RedirectResponse{$this->permissions->assert('crm');if(trim($subject)==='')throw new \InvalidArgumentException('Betreff fehlt.');$this->insert('re_erp_communications',['customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'type'=>$type,'subject'=>trim($subject),'details'=>$details,'contact_at'=>$this->dt($contactAt)??date('Y-m-d H:i:s'),'follow_up_at'=>$this->dt($followUpAt),'created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.crm');}
 #[NoAdminRequired,NoCSRFRequired] public function offers():TemplateResponse{$this->permissions->assert('offers');return $this->page('offers',['offers'=>$this->offersRows()]);}
 #[NoAdminRequired,NoCSRFRequired] public function offerForm():TemplateResponse{$this->permissions->assert('offers');return $this->page('offer_form',['offer'=>null,'items'=>[],'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no')]);}
 #[NoAdminRequired,NoCSRFRequired] public function editOffer(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();
  if($this->oneBy('re_erp_orders','offer_id',$id))throw new \InvalidArgumentException('Das Angebot wurde bereits in einen Auftrag übernommen und kann nicht mehr bearbeitet werden.');
  return $this->page('offer_form',['offer'=>$offer,'items'=>$this->where('re_erp_offer_items','offer_id',$id,'position_no'),'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no')]);
 }
 #[NoAdminRequired] public function saveOffer(int $customerId,string $title,array $descriptions=[],array $quantities=[],array $units=[],array $unitPrices=[],array $alternatives=[],?int $projectId=null,?string $validUntil=null,float $vatRate=19,?string $notes=null,?string $subject=null,?string $introText=null,?string $outroText=null):RedirectResponse{
  $this->permissions->assert('offers');
  $title=trim($title);if($title==='')throw new \InvalidArgumentException('Titel fehlt.');
  if(!$this->one('re_erp_customers',$customerId))throw new \InvalidArgumentException('Kunde nicht gefunden.');
  $items=[];$count=max(count($descriptions),count($quantities),count($units),count($unitPrices));$positionImages=$this->positionImages($count);
  for($idx=0;$idx<$count;$idx++){
   $description=$this->cleanRichText((string)($descriptions[$idx]??''))??'';
   $qty=(float)str_replace(',','.',(string)($quantities[$idx]??'0'));
   $unit=trim((string)($units[$idx]??'Stk.')) ?: 'Stk.';
   $unitPrice=(float)str_replace(',','.',(string)($unitPrices[$idx]??'0'));
   if($description===''&&$qty<=0&&$unitPrice==0.0)continue;
   if($description==='')throw new \InvalidArgumentException('Bei jeder Position ist eine Beschreibung erforderlich.');
   if($qty<=0)throw new \InvalidArgumentException('Die Menge muss größer als 0 sein.');
   if($unitPrice<0)throw new \InvalidArgumentException('Der Einzelpreis darf nicht negativ sein.');
   $items[]=['description'=>$description,'quantity'=>$qty,'unit'=>$unit,'unit_price'=>$unitPrice,'total_price'=>round($qty*$unitPrice,2),'is_alternative'=>!empty($alternatives[$idx])]+($positionImages[$idx]??[]);
  }
  if($items===[])throw new \InvalidArgumentException('Mindestens eine Angebotsposition ist erforderlich.');
  $vat=max(0,min(100,$vatRate));
  $net=round(array_sum(array_map(static fn(array $x):float=>!empty($x['is_alternative'])?0.0:(float)$x['total_price'],$items)),2);
  $gross=round($net*(1+$vat/100),2);$now=date('Y-m-d H:i:s');
  $this->db->beginTransaction();
  try{
   $id=$this->insert('re_erp_offers',['offer_no'=>$this->numbers->next('offer'),'customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'title'=>$title,'subject'=>trim((string)$subject)?:$title,'intro_text'=>$this->cleanRichText($introText),'outro_text'=>$this->cleanRichText($outroText),'offer_date'=>date('Y-m-d'),'valid_until'=>$validUntil?:null,'status'=>'draft','notes'=>$this->cleanRichText($notes),'net_amount'=>$net,'vat_rate'=>$vat,'gross_amount'=>$gross,'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now]);
   foreach($items as $idx=>$item)$this->insert('re_erp_offer_items',['offer_id'=>$id,'position_no'=>$idx+1,'description'=>$item['description'],'quantity'=>$item['quantity'],'unit'=>$item['unit'],'unit_price'=>$item['unit_price'],'total_price'=>$item['total_price'],'is_alternative'=>!empty($item['is_alternative']),'image_name'=>$item['image_name']??null,'image_mime'=>$item['image_mime']??null,'image_data'=>$item['image_data']??null]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function updateOffer(int $id,int $customerId,string $title,array $descriptions=[],array $quantities=[],array $units=[],array $unitPrices=[],array $alternatives=[],?int $projectId=null,?string $validUntil=null,float $vatRate=19,?string $notes=null,?string $subject=null,?string $introText=null,?string $outroText=null):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();
  if($this->oneBy('re_erp_orders','offer_id',$id))throw new \InvalidArgumentException('Das Angebot wurde bereits in einen Auftrag übernommen und kann nicht mehr bearbeitet werden.');
  $title=trim($title);if($title==='')throw new \InvalidArgumentException('Titel fehlt.');if(!$this->one('re_erp_customers',$customerId))throw new \InvalidArgumentException('Kunde nicht gefunden.');
  $oldItems=$this->where('re_erp_offer_items','offer_id',$id,'position_no');$items=[];$count=max(count($descriptions),count($quantities),count($units),count($unitPrices));$positionImages=$this->positionImages($count,$oldItems);
  for($idx=0;$idx<$count;$idx++){
   $description=$this->cleanRichText((string)($descriptions[$idx]??''))??'';$qty=(float)str_replace(',','.',(string)($quantities[$idx]??'0'));$unit=trim((string)($units[$idx]??'Stk.')) ?: 'Stk.';$unitPrice=(float)str_replace(',','.',(string)($unitPrices[$idx]??'0'));
   if($description===''&&$qty<=0&&$unitPrice==0.0)continue;if($description==='')throw new \InvalidArgumentException('Bei jeder Position ist eine Beschreibung erforderlich.');if($qty<=0)throw new \InvalidArgumentException('Die Menge muss größer als 0 sein.');if($unitPrice<0)throw new \InvalidArgumentException('Der Einzelpreis darf nicht negativ sein.');
   $items[]=['description'=>$description,'quantity'=>$qty,'unit'=>$unit,'unit_price'=>$unitPrice,'total_price'=>round($qty*$unitPrice,2),'is_alternative'=>!empty($alternatives[$idx])]+($positionImages[$idx]??[]);
  }
  if($items===[])throw new \InvalidArgumentException('Mindestens eine Angebotsposition ist erforderlich.');$vat=max(0,min(100,$vatRate));$net=round(array_sum(array_map(static fn(array $x):float=>!empty($x['is_alternative'])?0.0:(float)$x['total_price'],$items)),2);$gross=round($net*(1+$vat/100),2);
  $this->db->beginTransaction();try{
   $this->update('re_erp_offers',$id,['customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'title'=>$title,'subject'=>trim((string)$subject)?:$title,'intro_text'=>$this->cleanRichText($introText),'outro_text'=>$this->cleanRichText($outroText),'valid_until'=>$validUntil?:null,'notes'=>$this->cleanRichText($notes),'net_amount'=>$net,'vat_rate'=>$vat,'gross_amount'=>$gross,'updated_at'=>date('Y-m-d H:i:s')]);
   $q=$this->db->getQueryBuilder();$q->delete('re_erp_offer_items')->where($q->expr()->eq('offer_id',$q->createNamedParameter($id)));$q->executeStatement();
   foreach($items as $idx=>$item)$this->insert('re_erp_offer_items',['offer_id'=>$id,'position_no'=>$idx+1,'description'=>$item['description'],'quantity'=>$item['quantity'],'unit'=>$item['unit'],'unit_price'=>$item['unit_price'],'total_price'=>$item['total_price'],'is_alternative'=>!empty($item['is_alternative']),'image_name'=>$item['image_name']??null,'image_mime'=>$item['image_mime']??null,'image_data'=>$item['image_data']??null]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function offerDetail(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{$this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();return $this->page('offer_detail',['offer'=>$offer,'items'=>$this->where('re_erp_offer_items','offer_id',$id,'position_no'),'customer'=>$this->one('re_erp_customers',(int)$offer['customer_id']),'company'=>$this->companyData(),'linkedOrder'=>$this->oneBy('re_erp_orders','offer_id',$id),'hasOrder'=>(bool)$this->oneBy('re_erp_orders','offer_id',$id)]);}
 #[NoAdminRequired,NoCSRFRequired] public function offerPrint(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{$this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();$logo=$this->folders->companyLogo();return new TemplateResponse($this->appName,'offer_print',['offer'=>$offer,'customer'=>$this->one('re_erp_customers',(int)$offer['customer_id']),'project'=>$offer['project_id']?$this->one('re_erp_projects',(int)$offer['project_id']):null,'items'=>$this->where('re_erp_offer_items','offer_id',$id,'position_no'),'company'=>$this->companyData(),'logoDataUri'=>$logo?'data:'.$logo['mime'].';base64,'.base64_encode($logo['content']):null],'blank');}
 #[NoAdminRequired,NoCSRFRequired] public function offerPdf(int $id):DataDownloadResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();
  $customer=$this->one('re_erp_customers',(int)$offer['customer_id']);
  $project=!empty($offer['project_id'])?$this->one('re_erp_projects',(int)$offer['project_id']):null;
  $logo=$this->folders->companyLogo();
  $pdf=$this->pdf->createCommercialDocument('offer',$offer,$customer,$project,$this->where('re_erp_offer_items','offer_id',$id,'position_no'),$logo,$this->companyData());
  $name=preg_replace('/[^A-Za-z0-9._-]+/','_',trim((string)($offer['offer_no']??'Angebot'))).'_Angebot.pdf';
  return new DataDownloadResponse($pdf,$name,'application/pdf');
 }
 #[NoAdminRequired] public function sendOfferEmail(int $id,string $to,string $subject,string $body,int $attachPdf=1):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();
  $customer=$this->one('re_erp_customers',(int)$offer['customer_id']);
  $project=!empty($offer['project_id'])?$this->one('re_erp_projects',(int)$offer['project_id']):null;
  $attachments=[];
  if($attachPdf===1){
   $logo=$this->folders->companyLogo();
   $pdf=$this->pdf->createCommercialDocument('offer',$offer,$customer,$project,$this->where('re_erp_offer_items','offer_id',$id,'position_no'),$logo,$this->companyData());
   $number=preg_replace('/[^A-Za-z0-9._-]+/','_',trim((string)($offer['offer_no']??'Angebot')));
   $attachments[]=['name'=>$number.'_Angebot.pdf','data'=>$pdf,'mime'=>'application/pdf'];
  }
  $company=$this->companyData();
  $this->mail->send($to,$subject,$body,$attachments,(string)($company['email']??''));
  $this->update('re_erp_offers',$id,['status'=>'sent','updated_at'=>date('Y-m-d H:i:s')]);
  return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function updateOfferStatus(int $id,string $status):RedirectResponse{$this->permissions->assert('offers');if(!in_array($status,['draft','sent','accepted','rejected','expired'],true))throw new \InvalidArgumentException('Ungültiger Status.');$this->update('re_erp_offers',$id,['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.offerDetail',['id'=>$id]);}
 #[NoAdminRequired] public function deleteOffer(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('offers');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();
  if($this->oneBy('re_erp_orders','offer_id',$id))throw new \InvalidArgumentException('Angebot kann nicht gelöscht werden, weil bereits ein Auftrag daraus entstanden ist.');
  $this->db->beginTransaction();try{$q=$this->db->getQueryBuilder();$q->delete('re_erp_offer_items')->where($q->expr()->eq('offer_id',$q->createNamedParameter($id)))->executeStatement();$q=$this->db->getQueryBuilder();$q->delete('re_erp_offers')->where($q->expr()->eq('id',$q->createNamedParameter($id)))->executeStatement();$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.offers');
 }
 #[NoAdminRequired] public function createOrderFromOffer(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{$this->permissions->assert('orders');$offer=$this->offer($id);if(!$offer)return new \OCP\AppFramework\Http\NotFoundResponse();$existing=$this->oneBy('re_erp_orders','offer_id',$id);if($existing)return $this->go('reinhardterp.business.orderDetail',['id'=>$existing['id']]);$now=date('Y-m-d H:i:s');$orderId=$this->insert('re_erp_orders',['order_no'=>$this->numbers->next('order'),'offer_id'=>$id,'customer_id'=>$offer['customer_id'],'project_id'=>$offer['project_id'],'title'=>$offer['title'],'order_date'=>date('Y-m-d'),'status'=>'open','net_amount'=>$offer['net_amount'],'gross_amount'=>$offer['gross_amount'],'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now]);foreach($this->where('re_erp_offer_items','offer_id',$id,'position_no') as $i){$this->insert('re_erp_order_items',['order_id'=>$orderId,'position_no'=>$i['position_no'],'description'=>$i['description'],'quantity'=>$i['quantity'],'unit'=>$i['unit'],'unit_price'=>$i['unit_price'],'total_price'=>$i['total_price']]);}$this->update('re_erp_offers',$id,['status'=>'accepted','updated_at'=>$now]);return $this->go('reinhardterp.business.orderDetail',['id'=>$orderId]);}

 #[NoAdminRequired,NoCSRFRequired] public function invoices():TemplateResponse{
  $this->permissions->assert('invoices');
  return $this->page('invoices',['invoices'=>$this->invoiceRows()]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function invoiceForm(?int $orderId=null,?string $invoiceType=null,?string $installmentMode=null,?float $installmentPercent=null,?float $installmentAmount=null,?int $projectId=null,?int $includeTimes=0,?int $includeMaterials=0,?int $includeReports=0):TemplateResponse{
  $this->permissions->assert('invoices');
  $prefillOrder=null;$prefillItems=[];$prefillType=in_array($invoiceType,['invoice','advance','final'],true)?$invoiceType:'invoice';$prefillPercent=null;$prefillAmount=null;$prefillMode=in_array($installmentMode,['percent','amount'],true)?$installmentMode:null;
  if($orderId&&$orderId>0){
   $prefillOrder=$this->order($orderId);
   if($prefillOrder){
    if($prefillType==='advance' && $prefillMode!==null){
     $baseNet=(float)($prefillOrder['net_amount']??0);
     if($prefillMode==='percent'){
      $pct=max(0.01,min(100,(float)$installmentPercent));$prefillPercent=$pct;$amount=round($baseNet*$pct/100,2);
      $label=rtrim(rtrim(number_format($pct,2,'.',''),'0'),'.').'% Abschlag auf Auftrag '.(string)$prefillOrder['order_no'];
     }else{
      $amount=max(0.01,round((float)$installmentAmount,2));$prefillAmount=$amount;
      $label='Abschlag auf Auftrag '.(string)$prefillOrder['order_no'];
     }
     $prefillItems=[['description'=>$label,'quantity'=>1,'unit'=>'Pausch.','unit_price'=>$amount,'total_price'=>$amount]];
    }elseif($prefillType!=='advance'){
     $prefillItems=$this->where('re_erp_order_items','order_id',$orderId,'position_no');
    }
   }
  }
  if($projectId && $projectId>0 && ($includeTimes || $includeMaterials || $includeReports)){
   $project=$this->one('re_erp_projects',$projectId);
   if($project){$projectItems=$this->projectInvoiceItems($projectId,$includeTimes===1,$includeMaterials===1,$includeReports===1);$prefillItems=($orderId&&$orderId>0)?array_merge($prefillItems,$projectItems):$projectItems;}
  }
  return $this->page('invoice_form',[
   'invoices'=>$this->invoiceRows(),
   'customers'=>$this->rows('re_erp_customers','name'),
   'projects'=>$this->rows('re_erp_projects','project_no'),
   'prefillOrder'=>$prefillOrder,
   'prefillItems'=>$prefillItems,
   'prefillType'=>$prefillType,
   'prefillPercent'=>$prefillPercent,
   'prefillAmount'=>$prefillAmount,
   'prefillMode'=>$prefillMode,
   'prefillProjectId'=>$projectId,
   'prefillProject'=>$projectId?$this->one('re_erp_projects',$projectId):null,
   'defaultClerkName'=>$this->currentClerkName(),
   'orders'=>$this->rows('re_erp_orders','id','DESC'),
   'paymentTerms'=>$this->paymentTerms(),
   'defaultPaymentTerm'=>$this->config->getAppValue($this->appName,'payment_terms_default','net14'),
  ]);
 }
 #[NoAdminRequired] public function saveInvoice(int $customerId,array $descriptions=[],array $quantities=[],array $units=[],array $unitPrices=[],array $alternatives=[],?int $projectId=null,?int $orderId=null,?string $invoiceDate=null,?string $serviceDate=null,?string $dueDate=null,float $vatRate=19,?string $taxMode='standard19',?string $paymentTermKey=null,?string $notes=null,string $invoiceType='invoice',?string $clerkName=null,?string $subject=null,?string $introText=null,?string $outroText=null,?float $installmentPercent=null,?float $installmentBaseNet=null):RedirectResponse{
  $this->permissions->assert('invoices');
  $customer=$this->one('re_erp_customers',$customerId);if(!$customer)throw new \InvalidArgumentException('Kunde nicht gefunden.');
  $items=[];$count=max(count($descriptions),count($quantities),count($units),count($unitPrices));$positionImages=$this->positionImages($count);
  for($idx=0;$idx<$count;$idx++){
   $description=$this->cleanRichText((string)($descriptions[$idx]??''))??'';$qty=(float)str_replace(',','.',(string)($quantities[$idx]??'0'));$unit=trim((string)($units[$idx]??'Stk.'))?:'Stk.';$unitPrice=(float)str_replace(',','.',(string)($unitPrices[$idx]??'0'));
   if($description===''&&$qty<=0&&$unitPrice==0.0)continue;
   if($description==='')throw new \InvalidArgumentException('Bei jeder Position ist eine Beschreibung erforderlich.');
   if($qty<=0)throw new \InvalidArgumentException('Die Menge muss größer als 0 sein.');
   if($unitPrice<0)throw new \InvalidArgumentException('Der Einzelpreis darf nicht negativ sein.');
   $items[]=['description'=>$description,'quantity'=>$qty,'unit'=>$unit,'unit_price'=>$unitPrice,'total_price'=>round($qty*$unitPrice,2),'is_alternative'=>!empty($alternatives[$idx])]+($positionImages[$idx]??[]);
  }
  if($items===[])throw new \InvalidArgumentException('Mindestens eine Rechnungsposition ist erforderlich.');
  $tax=$this->taxMode((string)$taxMode,$vatRate);$vat=$tax['rate'];$net=round(array_sum(array_map(static fn(array $x):float => !empty($x['is_alternative']) ? 0.0 : (float)$x['total_price'],$items)),2);$gross=round($net*(1+$vat/100),2);$now=date('Y-m-d H:i:s');
  $invoiceType=in_array($invoiceType,['invoice','advance','final'],true)?$invoiceType:'invoice';
  $advanceNet=0.0;$advanceGross=0.0;
  if($invoiceType==='final'&&$orderId&&$orderId>0){
   $q=$this->db->getQueryBuilder();
   $q->select('net_amount','gross_amount')->from('re_erp_invoices')
     ->where($q->expr()->eq('order_id',$q->createNamedParameter($orderId)))
     ->andWhere($q->expr()->eq('invoice_type',$q->createNamedParameter('advance')))
     ->andWhere($q->expr()->neq('status',$q->createNamedParameter('cancelled')));
   foreach($q->executeQuery()->fetchAllAssociative() as $a){
    $advanceNet+=(float)$a['net_amount'];
    $advanceGross+=(float)$a['gross_amount'];
   }
  }
  $draftNo='ENTWURF-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(3)),0,6);
  $this->db->beginTransaction();
  try{
   $id=$this->insert('re_erp_invoices',[
    'invoice_no'=>$draftNo,'customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'order_id'=>$orderId&&$orderId>0?$orderId:null,
    'invoice_date'=>$invoiceDate?:date('Y-m-d'),'service_date'=>$serviceDate?:null,'due_date'=>$dueDate?:null,'status'=>'draft','invoice_type'=>$invoiceType,
    'net_amount'=>$net,'vat_rate'=>$vat,'gross_amount'=>$gross,'tax_mode'=>$tax['mode'],'tax_note'=>$tax['note'],'payment_term_key'=>trim((string)$paymentTermKey)?:null,'advance_net_amount'=>$advanceNet,'advance_gross_amount'=>$advanceGross,'folder_path'=>null,'notes'=>$this->cleanRichText($notes),'clerk_name'=>trim((string)$clerkName)!==''?trim((string)$clerkName):$this->currentClerkName(),'subject'=>trim((string)$subject)?:null,'intro_text'=>$this->cleanRichText($introText),'outro_text'=>$this->cleanRichText($outroText),
    'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now
   ]);
   foreach($items as $item)$this->insert('re_erp_invoice_items',['invoice_id'=>$id,'source_type'=>$orderId?'order':'manual','source_id'=>$orderId?:null,'description'=>$item['description'],'quantity'=>$item['quantity'],'unit'=>$item['unit'],'unit_price'=>$item['unit_price'],'total_price'=>$item['total_price'],'is_alternative'=>!empty($item['is_alternative']),'image_name'=>$item['image_name']??null,'image_mime'=>$item['image_mime']??null,'image_data'=>$item['image_data']??null]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function invoiceDetail(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  $company=$this->invoiceCompany($invoice);$customer=$this->invoiceCustomer($invoice);$items=$this->where('re_erp_invoice_items','invoice_id',$id,'id');
  return $this->page('invoice_detail',['invoice'=>$invoice,'items'=>$items,'company'=>$company,'invoiceCustomer'=>$customer,'eInvoiceWarnings'=>$this->xrechnung->warnings($invoice,$customer,$items,$company),'previousInstallments'=>$this->previousInstallments($invoice),'payments'=>$this->where('re_erp_invoice_payments','invoice_id',$id,'payment_date'),'clerkName'=>trim((string)($invoice['clerk_name']??''))!==''?(string)$invoice['clerk_name']:$this->currentClerkName(),'sourceOrder'=>!empty($invoice['order_id'])?$this->order((int)$invoice['order_id']):null,'sourceOffer'=>!empty($invoice['order_id'])?$this->offerForOrder((int)$invoice['order_id']):null,'relatedInvoice'=>!empty($invoice['related_invoice_id'])?$this->invoice((int)$invoice['related_invoice_id']):null,'creditNotes'=>$this->creditNotesForInvoice($id)]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function invoicePrint(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();if(trim((string)($invoice['clerk_name']??''))==='')$invoice['clerk_name']=$this->currentClerkName();$logo=$this->folders->companyLogo();
  return new TemplateResponse($this->appName,'invoice_print',['invoice'=>$invoice,'items'=>$this->where('re_erp_invoice_items','invoice_id',$id,'id'),'company'=>$this->invoiceCompany($invoice),'customer'=>$this->invoiceCustomer($invoice),'project'=>$invoice['project_id']?$this->one('re_erp_projects',(int)$invoice['project_id']):null,'logoDataUri'=>$logo?'data:'.$logo['mime'].';base64,'.base64_encode($logo['content']):null],'blank');
 }
 #[NoAdminRequired,NoCSRFRequired] public function invoicePdf(int $id):DataDownloadResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if(trim((string)($invoice['clerk_name']??''))==='')$invoice['clerk_name']=$this->currentClerkName();
  $customer=$this->invoiceCustomer($invoice);
  $project=!empty($invoice['project_id'])?$this->one('re_erp_projects',(int)$invoice['project_id']):null;
  $logo=$this->folders->companyLogo();
  $pdf=$this->pdf->createCommercialDocument((string)($invoice['invoice_type']??'invoice'),$invoice,$customer,$project,$this->where('re_erp_invoice_items','invoice_id',$id,'id'),$logo,$this->invoiceCompany($invoice));
  $type=(string)($invoice['invoice_type']??'invoice');$suffix=match($type){'advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift',default=>'Rechnung'};
  $number=(string)($invoice['status']==='draft'?'Entwurf_'.$id:$invoice['invoice_no']);
  $name=preg_replace('/[^A-Za-z0-9._-]+/','_',$number.'_'.$suffix).'.pdf';
  return new DataDownloadResponse($pdf,$name,'application/pdf');
 }
 #[NoAdminRequired,NoCSRFRequired] public function invoiceXRechnung(int $id):DataDownloadResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  $customer=$this->invoiceCustomer($invoice);
  $items=$this->where('re_erp_invoice_items','invoice_id',$id,'id');
  $xml=$this->xrechnung->create($invoice,$customer,$items,$this->invoiceCompany($invoice));
  $number=preg_replace('/[^A-Za-z0-9._-]+/','_',trim((string)($invoice['invoice_no']??('Rechnung_'.$id))));
  return new DataDownloadResponse($xml,$number.'_XRechnung.xml','application/xml; charset=UTF-8');
 }
 #[NoAdminRequired] public function sendInvoiceEmail(int $id,string $to,string $subject,string $body,int $attachPdf=1,int $attachXml=0):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$invoice['status']==='draft')throw new \InvalidArgumentException('Ein Rechnungsentwurf muss vor dem Versand finalisiert werden.');
  if(trim((string)($invoice['clerk_name']??''))==='')$invoice['clerk_name']=$this->currentClerkName();
  $customer=$this->invoiceCustomer($invoice);
  $project=!empty($invoice['project_id'])?$this->one('re_erp_projects',(int)$invoice['project_id']):null;
  $company=$this->invoiceCompany($invoice);
  $items=$this->where('re_erp_invoice_items','invoice_id',$id,'id');
  $attachments=[];
  $number=preg_replace('/[^A-Za-z0-9._-]+/','_',trim((string)($invoice['invoice_no']??('Rechnung_'.$id))));
  $type=(string)($invoice['invoice_type']??'invoice');
  $suffix=match($type){'advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift',default=>'Rechnung'};
  if($attachPdf===1){
   $logo=$this->folders->companyLogo();
   $pdf=$this->pdf->createCommercialDocument($type,$invoice,$customer,$project,$items,$logo,$company);
   $attachments[]=['name'=>$number.'_'.$suffix.'.pdf','data'=>$pdf,'mime'=>'application/pdf'];
  }
  if($attachXml===1){
   $xml=$this->xrechnung->create($invoice,$customer,$items,$company);
   $attachments[]=['name'=>$number.'_XRechnung.xml','data'=>$xml,'mime'=>'application/xml'];
  }
  if($attachments===[])throw new \InvalidArgumentException('Bitte mindestens PDF oder XRechnung als Anhang auswählen.');
  $this->mail->send($to,$subject,$body,$attachments,(string)($company['email']??''));
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function finalizeInvoice(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();if((string)$invoice['status']!=='draft')return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
  $customer=$this->one('re_erp_customers',(int)$invoice['customer_id']);if(!$customer)throw new \InvalidArgumentException('Kunde nicht gefunden.');
  $invoiceNo=$this->numbers->next('invoice');$folder=$this->folders->ensureInvoiceFolder((string)($customer['folder_path']??''),$invoiceNo);$now=date('Y-m-d H:i:s');
  $this->update('re_erp_invoices',$id,['invoice_no'=>$invoiceNo,'status'=>'open','folder_path'=>$folder,'company_snapshot'=>json_encode($this->companyData(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'customer_snapshot'=>json_encode($customer,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'finalized_at'=>$now,'updated_at'=>$now]);
  // Finalisierte Ausgangsrechnungen revisionsnah als PDF nach Jahr/Monat ablegen.
  $finalInvoice=$this->invoice($id);
  if($finalInvoice){
   if(trim((string)($finalInvoice['clerk_name']??''))==='')$finalInvoice['clerk_name']=$this->currentClerkName();
   $finalCustomer=$this->invoiceCustomer($finalInvoice);
   $project=!empty($finalInvoice['project_id'])?$this->one('re_erp_projects',(int)$finalInvoice['project_id']):null;
   $logo=$this->folders->companyLogo();
   $pdfContent=$this->pdf->createCommercialDocument((string)($finalInvoice['invoice_type']??'invoice'),$finalInvoice,$finalCustomer,$project,$this->where('re_erp_invoice_items','invoice_id',$id,'id'),$logo,$this->invoiceCompany($finalInvoice));
   $invoiceDate=(string)($finalInvoice['invoice_date']??date('Y-m-d'));$ts=strtotime($invoiceDate)?:time();
   $archive=$this->folders->ensureFolderPath('ERP','30_Finanzen/Ausgangsrechnungen/'.date('Y',$ts).'/'.date('m',$ts));
   $type=(string)($finalInvoice['invoice_type']??'invoice');$suffix=match($type){'advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift',default=>'Rechnung'};
   $customerName=trim((string)($finalCustomer['name']??''));
   $fileName=preg_replace('/[^A-Za-z0-9ÄÖÜäöüß._-]+/u','_',trim($invoiceNo.'_'.$suffix.($customerName!==''?'_'.$customerName:''))).'.pdf';
   $this->folders->write($archive,$fileName,$pdfContent);
  }
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function updateInvoiceStatus(int $id,string $status):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if(!in_array($status,['open','paid','cancelled'],true))throw new \InvalidArgumentException('Ungültiger Rechnungsstatus.');
  if((string)$invoice['status']==='draft')throw new \InvalidArgumentException('Ein Entwurf muss zuerst finalisiert werden.');
  $data=['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')];
  if($status==='paid')$data['paid_at']=date('Y-m-d H:i:s');
  if($status==='cancelled')$data['cancelled_at']=date('Y-m-d H:i:s');
  $this->update('re_erp_invoices',$id,$data);return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function deleteInvoice(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)($invoice['status']??'')!=='draft')throw new \InvalidArgumentException('Nur Rechnungsentwürfe dürfen gelöscht werden. Finalisierte Rechnungen bitte stornieren.');
  $this->db->beginTransaction();try{
   foreach(['re_erp_invoice_payments'=>'invoice_id','re_erp_invoice_items'=>'invoice_id'] as $table=>$col){$q=$this->db->getQueryBuilder();$q->delete($table)->where($q->expr()->eq($col,$q->createNamedParameter($id)))->executeStatement();}
   $q=$this->db->getQueryBuilder();$q->delete('re_erp_invoices')->where($q->expr()->eq('id',$q->createNamedParameter($id)))->executeStatement();$this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.invoices');
 }
 #[NoAdminRequired,NoCSRFRequired] public function orders():TemplateResponse{$this->permissions->assert('orders');return $this->page('orders',['orders'=>$this->ordersRows()]);}
 #[NoAdminRequired,NoCSRFRequired] public function orderDetail(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{$this->permissions->assert('orders');$order=$this->order($id);if(!$order)return new \OCP\AppFramework\Http\NotFoundResponse();return $this->page('order_detail',['order'=>$order,'items'=>$this->where('re_erp_order_items','order_id',$id,'position_no'),'notes'=>$this->orderNotes($id),'invoices'=>$this->where('re_erp_invoices','order_id',$id,'id'),'sourceOffer'=>!empty($order['offer_id'])?$this->offer((int)$order['offer_id']):null,'orderPayments'=>$this->paymentsForOrder($id)]);}
 #[NoAdminRequired] public function updateOrderStatus(int $id,string $status):RedirectResponse{$this->permissions->assert('orders');if(!in_array($status,['open','confirmed','production','installation','completed','cancelled'],true))throw new \InvalidArgumentException('Ungültiger Status.');$this->update('re_erp_orders',$id,['status'=>$status,'updated_at'=>date('Y-m-d H:i:s')]);return $this->go('reinhardterp.business.orderDetail',['id'=>$id]);}
 #[NoAdminRequired] public function saveOrderNote(int $id,string $noteType,string $content):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
	$this->permissions->assert('orders');

	$order=$this->order($id);
	if(!$order){
		return new \OCP\AppFramework\Http\NotFoundResponse();
	}

	$allowed=['measurement','note','meeting','phone'];
	if(!in_array($noteType,$allowed,true)){
		throw new \InvalidArgumentException('Ungültige Notizart.');
	}

	$content=trim($content);
	if($content===''){
		throw new \InvalidArgumentException('Bitte einen Notiztext eingeben.');
	}

	$now=date('Y-m-d H:i:s');

	$this->insert('re_erp_order_notes',[
		'order_id'=>$id,
		'note_type'=>$noteType,
		'content'=>$content,
		'created_by'=>$this->uid(),
		'created_at'=>$now,
		'updated_at'=>null,
	]);

	return $this->go('reinhardterp.business.orderDetail',['id'=>$id]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function inventory():TemplateResponse{$this->permissions->assert('inventory');return $this->page('inventory',['materials'=>$this->materialsWithMeta(),'movements'=>$this->stockRows(),'projects'=>$this->rows('re_erp_projects','project_no')]);}
 #[NoAdminRequired] public function saveStockMovement(int $materialId,string $movementType,float $quantity,?int $projectId=null,?string $note=null,?float $purchasePrice=null,?float $saleMarkupPercent=null):RedirectResponse{$this->permissions->assert('inventory');if(!in_array($movementType,['in','out','adjustment'],true))throw new \InvalidArgumentException('Ungültige Lagerbewegung.');$m=$this->one('re_erp_materials',$materialId);if(!$m)throw new \InvalidArgumentException('Material nicht gefunden.');$qty=abs($quantity);$current=(float)($m['stock_quantity']??0);$new=$movementType==='in'?$current+$qty:($movementType==='out'?$current-$qty:$quantity);if($new<0)throw new \InvalidArgumentException('Lagerbestand darf nicht negativ werden.');$materialUpdate=['stock_quantity'=>$new];if($movementType==='in'){$ek=$purchasePrice!==null?max(0,(float)$purchasePrice):(float)($m['purchase_price']??$m['price']??0);if($purchasePrice!==null)$materialUpdate['purchase_price']=$ek;if($saleMarkupPercent!==null){$markup=max(0,(float)$saleMarkupPercent);$materialUpdate['sale_price']=round($ek*(1+($markup/100)),2);}}$this->db->beginTransaction();try{$this->insert('re_erp_stock_movements',['material_id'=>$materialId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'movement_type'=>$movementType,'quantity'=>$movementType==='out'?-1*$qty:($movementType==='in'?$qty:$quantity),'note'=>$note,'created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);$this->update('re_erp_materials',$materialId,$materialUpdate);$this->db->commit();}catch(\Throwable $e){$this->db->rollBack();throw $e;}return $this->go('reinhardterp.business.inventory');}
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
 #[NoAdminRequired,NoCSRFRequired] public function privacy():TemplateResponse{
  $this->permissions->assert('settings');
  return $this->page('privacy',['urlGenerator'=>$this->url]);
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

 #[NoAdminRequired] public function saveMobileProjectNote(int $id,string $noteType,string $content):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('mobile');
  $this->permissions->assertProjectAccess($id);

  $project=$this->one('re_erp_projects',$id);
  if(!$project){
   return new \OCP\AppFramework\Http\NotFoundResponse();
  }

  $allowed=['measurement','note','meeting','phone'];
  if(!in_array($noteType,$allowed,true)){
   throw new \InvalidArgumentException('Ungültige Notizart.');
  }

  $content=trim($content);
  if($content===''){
   throw new \InvalidArgumentException('Bitte einen Notiztext eingeben.');
  }

  $now=date('Y-m-d H:i:s');

  $this->insert('re_erp_order_notes',[
   'order_id'=>null,
   'project_id'=>$id,
   'note_type'=>$noteType,
   'content'=>$content,
   'created_by'=>$this->uid(),
   'created_at'=>$now,
   'updated_at'=>null,
  ]);

  return $this->go('reinhardterp.business.mobileProject',['id'=>$id]);
 }

 #[NoAdminRequired,NoCSRFRequired] public function mobileProject(int $id):TemplateResponse{$this->addPwaHeaders();
  $this->permissions->assert('mobile');$uid=$this->uid();$this->permissions->assertProjectAccess($id);
  $project=$this->one('re_erp_projects',$id);if(!$project)throw new \InvalidArgumentException('Projekt nicht gefunden.');
  $customer=!empty($project['customer_id'])?$this->one('re_erp_customers',(int)$project['customer_id']):null;
  return $this->page('mobile_project',[
   'project'=>$project,
   'customer'=>$customer,
   'notes'=>$this->projectNotes($id),
   'urlGenerator'=>$this->url
  ]);
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
  $manifest=$this->url->linkToRoute('reinhardterp.page.pwaManifest').'?v=betrio';
  $icon=$this->url->linkToRoute('reinhardterp.page.pwaIcon',['size'=>'192']);
  Util::addHeader('link',['rel'=>'manifest','href'=>$manifest]);
  Util::addHeader('meta',['name'=>'theme-color','content'=>'#1265d8']);
  Util::addHeader('meta',['name'=>'mobile-web-app-capable','content'=>'yes']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-capable','content'=>'yes']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-title','content'=>'Betrio']);
  Util::addHeader('meta',['name'=>'apple-mobile-web-app-status-bar-style','content'=>'default']);
  Util::addHeader('link',['rel'=>'apple-touch-icon','href'=>$icon]);
 }
 private function offlineOpMarker(?string $clientOperationId):?string{
  $id=trim((string)$clientOperationId);if($id===''||!preg_match('/^[a-zA-Z0-9._:-]{12,120}$/',$id))return null;
  return rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'nexterp-offline-'.hash('sha256',$this->uid().'|'.$id).'.done';
 }
 private function offlineOpDone(?string $clientOperationId):bool{$f=$this->offlineOpMarker($clientOperationId);return $f!==null&&is_file($f);}
 private function markOfflineOpDone(?string $clientOperationId):void{$f=$this->offlineOpMarker($clientOperationId);if($f!==null)@file_put_contents($f,(string)time(),LOCK_EX);}
 private function page(string $template,array $data):TemplateResponse{$data['appVersion']=$this->config->getAppValue($this->appName,'installed_version','unbekannt');return new TemplateResponse($this->appName,$template,$data);}
 private function go(string $route,array $params=[]):RedirectResponse{return new RedirectResponse($this->url->linkToRoute($route,$params));}
 private function uid():string{return $this->session->getUser()?->getUID()??'';}
 private function paymentTerms():array{
  $terms=[
   'due'=>['label'=>'Sofort ohne Abzug','days'=>0,'text'=>'Zahlbar sofort ohne Abzug.'],
   'net10'=>['label'=>'10 Tage netto','days'=>10,'text'=>'Zahlbar innerhalb von 10 Tagen ohne Abzug.'],
   'net14'=>['label'=>'14 Tage netto','days'=>14,'text'=>'Zahlbar innerhalb von 14 Tagen ohne Abzug.'],
   'net30'=>['label'=>'30 Tage netto','days'=>30,'text'=>'Zahlbar innerhalb von 30 Tagen ohne Abzug.'],
   'skonto2_10_30'=>['label'=>'2 % Skonto / 10 Tage, 30 Tage netto','days'=>30,'text'=>'2 % Skonto bei Zahlung innerhalb von 10 Tagen, ansonsten zahlbar innerhalb von 30 Tagen ohne Abzug.'],
   'skonto3_10_30'=>['label'=>'3 % Skonto / 10 Tage, 30 Tage netto','days'=>30,'text'=>'3 % Skonto bei Zahlung innerhalb von 10 Tagen, ansonsten zahlbar innerhalb von 30 Tagen ohne Abzug.'],
  ];
  for($i=1;$i<=3;$i++){ $label=trim($this->config->getAppValue($this->appName,'payment_custom_'.$i.'_label','')); $text=trim($this->config->getAppValue($this->appName,'payment_custom_'.$i.'_text','')); $days=(int)$this->config->getAppValue($this->appName,'payment_custom_'.$i.'_days','14'); if($label!==''&&$text!=='')$terms['custom'.$i]=['label'=>$label,'days'=>max(0,$days),'text'=>$text]; }
  return $terms;
 }
 private function taxMode(string $mode,float $manualRate=19):array{
  return match($mode){
   'standard7'=>['mode'=>'standard7','rate'=>7.0,'note'=>''],
   'small_business'=>['mode'=>'small_business','rate'=>0.0,'note'=>'Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG.'],
   'reverse_charge_13b'=>['mode'=>'reverse_charge_13b','rate'=>0.0,'note'=>'Steuerschuldnerschaft des Leistungsempfängers gemäß § 13b UStG.'],
   'custom'=>['mode'=>'custom','rate'=>max(0,min(100,$manualRate)),'note'=>''],
   default=>['mode'=>'standard19','rate'=>19.0,'note'=>''],
  };
 }

 private function currentClerkName():string{
  $user=$this->session->getUser();$name=trim((string)($user?->getDisplayName()??''));
  return $name!==''?$name:(string)($user?->getUID()??'');
 }

 private function companyData():array{
  $keys=['name','owner','street','zip','city','country','phone','mobile','email','website','taxNo','vatId','registerCourt','registerNo','bank1_name','bank1_iban','bank1_bic','bank2_name','bank2_iban','bank2_bic','default_offer_intro','default_offer_outro','default_invoice_note'];
  $out=[];foreach($keys as $key)$out[$key]=$this->config->getAppValue($this->appName,'company_'.$key,'');return $out;
 }
 #[NoAdminRequired,NoCSRFRequired] public function editInvoice(int $id):TemplateResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$invoice['status']!=='draft')return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
  return $this->page('invoice_edit',['invoice'=>$invoice,'items'=>$this->where('re_erp_invoice_items','invoice_id',$id,'id'),'customers'=>$this->rows('re_erp_customers','name'),'projects'=>$this->rows('re_erp_projects','project_no'),'paymentTerms'=>$this->paymentTerms(),'defaultPaymentTerm'=>$this->config->getAppValue($this->appName,'payment_terms_default','net14'),'clerkName'=>trim((string)($invoice['clerk_name']??''))!==''?(string)$invoice['clerk_name']:$this->currentClerkName()]);
 }
 #[NoAdminRequired] public function updateInvoice(int $id,int $customerId,array $descriptions=[],array $quantities=[],array $units=[],array $unitPrices=[],array $alternatives=[],?int $projectId=null,?string $invoiceDate=null,?string $serviceDate=null,?string $dueDate=null,float $vatRate=19,?string $taxMode='standard19',?string $paymentTermKey=null,?string $notes=null,string $invoiceType='invoice',?string $clerkName=null,?string $subject=null,?string $introText=null,?string $outroText=null):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$invoice['status']!=='draft')throw new \InvalidArgumentException('Finalisierte Rechnungen können nicht mehr bearbeitet werden.');
  if(!$this->one('re_erp_customers',$customerId))throw new \InvalidArgumentException('Kunde nicht gefunden.');
  $oldItems=$this->where('re_erp_invoice_items','invoice_id',$id,'id');$items=[];$count=max(count($descriptions),count($quantities),count($units),count($unitPrices));$positionImages=$this->positionImages($count,$oldItems);
  for($idx=0;$idx<$count;$idx++){
   $d=$this->cleanRichText((string)($descriptions[$idx]??''))??'';$q=(float)str_replace(',','.',(string)($quantities[$idx]??0));$u=trim((string)($units[$idx]??'Stk.'))?:'Stk.';$p=(float)str_replace(',','.',(string)($unitPrices[$idx]??0));
   if($d===''&&$q<=0&&$p==0)continue;if($d===''||$q<=0||$p<0)throw new \InvalidArgumentException('Bitte Rechnungspositionen vollständig prüfen.');
   $items[]=['description'=>$d,'quantity'=>$q,'unit'=>$u,'unit_price'=>$p,'total_price'=>round($q*$p,2),'is_alternative'=>!empty($alternatives[$idx])]+($positionImages[$idx]??[]);
  }
  if(!$items)throw new \InvalidArgumentException('Mindestens eine Rechnungsposition ist erforderlich.');
  $tax=$this->taxMode((string)$taxMode,$vatRate);$vat=$tax['rate'];$net=round(array_sum(array_map(static fn(array $x):float => !empty($x['is_alternative']) ? 0.0 : (float)$x['total_price'],$items)),2);$gross=round($net*(1+$vat/100),2);
  $type=in_array($invoiceType,['invoice','advance','final','credit'],true)?$invoiceType:'invoice';
  $this->db->beginTransaction();
  try{
   $this->update('re_erp_invoices',$id,['customer_id'=>$customerId,'project_id'=>$projectId&&$projectId>0?$projectId:null,'invoice_date'=>$invoiceDate?:date('Y-m-d'),'service_date'=>$serviceDate?:null,'due_date'=>$dueDate?:null,'vat_rate'=>$vat,'tax_mode'=>$tax['mode'],'tax_note'=>$tax['note'],'payment_term_key'=>trim((string)$paymentTermKey)?:null,'net_amount'=>$net,'gross_amount'=>$gross,'invoice_type'=>$type,'notes'=>$this->cleanRichText($notes),'clerk_name'=>trim((string)$clerkName)!==''?trim((string)$clerkName):$this->currentClerkName(),'subject'=>trim((string)$subject)?:null,'intro_text'=>$this->cleanRichText($introText),'outro_text'=>$this->cleanRichText($outroText),'updated_at'=>date('Y-m-d H:i:s')]);
   $q=$this->db->getQueryBuilder();$q->delete('re_erp_invoice_items')->where($q->expr()->eq('invoice_id',$q->createNamedParameter($id)))->executeStatement();
   foreach($items as $x)$this->insert('re_erp_invoice_items',['invoice_id'=>$id,'source_type'=>'manual','source_id'=>null,'description'=>$x['description'],'quantity'=>$x['quantity'],'unit'=>$x['unit'],'unit_price'=>$x['unit_price'],'total_price'=>$x['total_price'],'is_alternative'=>!empty($x['is_alternative']),'image_name'=>$x['image_name']??null,'image_mime'=>$x['image_mime']??null,'image_data'=>$x['image_data']??null]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function addInvoicePayment(int $id,float $amount,?string $paymentDate=null,?string $note=null):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$invoice['status']==='draft'||(string)$invoice['status']==='cancelled')throw new \InvalidArgumentException('Für diese Rechnung kann keine Zahlung erfasst werden.');
  if($amount<=0)throw new \InvalidArgumentException('Der Zahlbetrag muss größer als 0 sein.');
  $this->insert('re_erp_invoice_payments',['invoice_id'=>$id,'payment_date'=>$paymentDate?:date('Y-m-d'),'amount'=>$amount,'note'=>trim((string)$note)?:null,'created_by'=>$this->uid(),'created_at'=>date('Y-m-d H:i:s')]);
  $paid=$this->invoicePaidAmount($id);$due=$this->invoicePayableAmount($invoice);
  if($paid+0.005 >= $due)$this->update('re_erp_invoices',$id,['status'=>'paid','paid_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function deleteInvoicePayment(int $id,int $paymentId):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  $q=$this->db->getQueryBuilder();$q->delete('re_erp_invoice_payments')->where($q->expr()->eq('id',$q->createNamedParameter($paymentId)))->andWhere($q->expr()->eq('invoice_id',$q->createNamedParameter($id)))->executeStatement();
  if((string)$invoice['status']==='paid'&&$this->invoicePaidAmount($id)+0.005<$this->invoicePayableAmount($invoice))$this->update('re_erp_invoices',$id,['status'=>'open','paid_at'=>null,'updated_at'=>date('Y-m-d H:i:s')]);
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function advanceReminder(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$invoice=$this->invoice($id);if(!$invoice)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$invoice['status']!=='open')throw new \InvalidArgumentException('Mahnungen sind nur für offene Rechnungen möglich.');
  $level=min(3,(int)($invoice['reminder_level']??0)+1);
  $this->update('re_erp_invoices',$id,['reminder_level'=>$level,'last_reminder_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$id]);
 }
 #[NoAdminRequired] public function createCreditNote(int $id):RedirectResponse|\OCP\AppFramework\Http\NotFoundResponse{
  $this->permissions->assert('invoices');$source=$this->invoice($id);if(!$source)return new \OCP\AppFramework\Http\NotFoundResponse();
  if((string)$source['status']==='draft')throw new \InvalidArgumentException('Aus einem Entwurf kann keine Gutschrift erstellt werden.');
  if((string)($source['invoice_type']??'invoice')==='credit')throw new \InvalidArgumentException('Aus einer Gutschrift kann keine weitere Gutschrift erstellt werden.');
  $items=$this->where('re_erp_invoice_items','invoice_id',$id,'id');$now=date('Y-m-d H:i:s');
  $this->db->beginTransaction();
  try{
   $newId=$this->insert('re_erp_invoices',[
    'invoice_no'=>'ENTWURF-'.date('YmdHis').'-'.substr(bin2hex(random_bytes(3)),0,6),'customer_id'=>$source['customer_id'],'project_id'=>$source['project_id']?:null,'order_id'=>$source['order_id']?:null,
    'invoice_date'=>date('Y-m-d'),'service_date'=>$source['service_date']?:null,'due_date'=>null,'status'=>'draft','invoice_type'=>'credit','related_invoice_id'=>$id,
    'net_amount'=>-(float)$source['net_amount'],'vat_rate'=>(float)$source['vat_rate'],'gross_amount'=>-(float)$source['gross_amount'],'advance_net_amount'=>0,'advance_gross_amount'=>0,
    'folder_path'=>null,'notes'=>'Gutschrift zu Rechnung '.$source['invoice_no'],'created_by'=>$this->uid(),'created_at'=>$now,'updated_at'=>$now
   ]);
   foreach($items as $x)$this->insert('re_erp_invoice_items',['invoice_id'=>$newId,'source_type'=>'credit','source_id'=>$id,'description'=>$x['description'],'quantity'=>-(float)$x['quantity'],'unit'=>$x['unit'],'unit_price'=>(float)$x['unit_price'],'total_price'=>-(float)$x['total_price'],'is_alternative'=>!empty($x['is_alternative'])]);
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->go('reinhardterp.business.invoiceDetail',['id'=>$newId]);
 }
 private function cleanRichText(?string $value):?string{
  $value=trim((string)$value);if($value==='')return null;
  $value=preg_replace('#<(script|style|iframe|object|embed|form|input|button|svg|math)\b[^>]*>.*?</\1>#is','',$value);
  $value=strip_tags($value,'<p><br><b><strong><i><em><u><ul><ol><li><h2><h3><h4><a>');
  $value=preg_replace('/\s(on\w+|style|class|id)\s*=\s*(["\']).*?\2/is','',$value);
  $value=preg_replace('/href\s*=\s*(["\'])\s*javascript:.*?\1/is','',$value);
  return $value;
 }
 private function offerForOrder(int $orderId):?array{$order=$this->order($orderId);if(!$order||empty($order['offer_id']))return null;return $this->offer((int)$order['offer_id']);}
 private function creditNotesForInvoice(int $invoiceId):array{$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_invoices')->where($q->expr()->eq('related_invoice_id',$q->createNamedParameter($invoiceId)))->andWhere($q->expr()->eq('invoice_type',$q->createNamedParameter('credit')))->orderBy('id','DESC');return $q->executeQuery()->fetchAllAssociative();}
 private function paymentsForOrder(int $orderId):array{$q=$this->db->getQueryBuilder();$q->select('p.*','i.invoice_no','i.invoice_type')->from('re_erp_invoice_payments','p')->innerJoin('p','re_erp_invoices','i',$q->expr()->eq('i.id','p.invoice_id'))->where($q->expr()->eq('i.order_id',$q->createNamedParameter($orderId)))->orderBy('p.payment_date','DESC');return $q->executeQuery()->fetchAllAssociative();}
 private function projectInvoiceItems(int $projectId,bool $times,bool $materials,bool $reports):array{
  $items=[];
  if($times){$q=$this->db->getQueryBuilder();$q->select('e.activity','e.hours','w.user_id','w.work_date','ur.individual_hourly_rate','hr.sales_rate')->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->leftJoin('w','re_erp_user_roles','ur',$q->expr()->eq('ur.user_id','w.user_id'))->leftJoin('ur','re_erp_hourly_rates','hr',$q->expr()->eq('hr.id','ur.hourly_rate_id'))->where($q->expr()->eq('e.project_id',$q->createNamedParameter($projectId)))->orderBy('w.work_date','ASC');foreach($q->executeQuery()->fetchAllAssociative() as $r){$rate=(float)($r['individual_hourly_rate']??0);if($rate<=0)$rate=(float)($r['sales_rate']??0);$hours=(float)($r['hours']??0);if($hours<=0)continue;$items[]=['description'=>'Arbeitszeit '.(string)($r['work_date']??'').' · '.trim((string)($r['activity']??'Arbeitsleistung')).' · '.(string)($r['user_id']??''),'quantity'=>$hours,'unit'=>'Std.','unit_price'=>$rate,'total_price'=>round($hours*$rate,2)];}}
  if($materials){$q=$this->db->getQueryBuilder();$q->select('ri.description','ri.quantity','ri.unit','m.sale_price','m.price','r.report_no')->from('re_erp_report_items','ri')->innerJoin('ri','re_erp_reports','r',$q->expr()->eq('r.id','ri.report_id'))->leftJoin('ri','re_erp_materials','m',$q->expr()->eq('m.id','ri.material_id'))->where($q->expr()->eq('r.project_id',$q->createNamedParameter($projectId)))->orderBy('r.report_date','ASC');foreach($q->executeQuery()->fetchAllAssociative() as $r){$qty=(float)($r['quantity']??0);if($qty<=0)continue;$price=(float)($r['sale_price']??0);if($price<=0)$price=(float)($r['price']??0);$items[]=['description'=>trim((string)$r['description']).(!empty($r['report_no'])?' · Rapport '.$r['report_no']:''),'quantity'=>$qty,'unit'=>trim((string)($r['unit']??''))?:'Stk.','unit_price'=>$price,'total_price'=>round($qty*$price,2)];}}
  if($reports){$q=$this->db->getQueryBuilder();$q->select('report_no','report_date','title','signed_at')->from('re_erp_reports')->where($q->expr()->eq('project_id',$q->createNamedParameter($projectId)))->andWhere($q->expr()->eq('archived',$q->createNamedParameter(0)))->orderBy('report_date','ASC');foreach($q->executeQuery()->fetchAllAssociative() as $r){$desc='Rapport '.(string)$r['report_no'].' vom '.(string)$r['report_date'].' · '.(string)$r['title'];if(!empty($r['signed_at']))$desc.=' · unterschrieben';$items[]=['description'=>$desc,'quantity'=>1,'unit'=>'Info','unit_price'=>0.0,'total_price'=>0.0,'is_alternative'=>1];}}
  return $items;
 }
 private function invoicePaidAmount(int $invoiceId):float{
  $q=$this->db->getQueryBuilder();$q->selectAlias($q->func()->sum('amount'),'total')->from('re_erp_invoice_payments')->where($q->expr()->eq('invoice_id',$q->createNamedParameter($invoiceId)));$r=$q->executeQuery()->fetchOne();return round((float)($r?:0),2);
 }
 private function invoicePayableAmount(array $invoice):float{
  $gross=(float)$invoice['gross_amount'];if(($invoice['invoice_type']??'invoice')==='final')$gross-=(float)($invoice['advance_gross_amount']??0);return max(0,round($gross,2));
 }
 private function invoiceRows():array{
  $q=$this->db->getQueryBuilder();$q->select('i.*','c.name AS customer_name','p.project_no','o.order_no')->from('re_erp_invoices','i')->leftJoin('i','re_erp_customers','c',$q->expr()->eq('c.id','i.customer_id'))->leftJoin('i','re_erp_projects','p',$q->expr()->eq('p.id','i.project_id'))->leftJoin('i','re_erp_orders','o',$q->expr()->eq('o.id','i.order_id'))->orderBy('i.invoice_date','DESC')->addOrderBy('i.id','DESC');return $q->executeQuery()->fetchAllAssociative();
 }
 private function invoice(int $id):?array{
  $q=$this->db->getQueryBuilder();$q->select('i.*','c.name AS customer_name','p.project_no','p.title AS project_title','o.order_no')->from('re_erp_invoices','i')->leftJoin('i','re_erp_customers','c',$q->expr()->eq('c.id','i.customer_id'))->leftJoin('i','re_erp_projects','p',$q->expr()->eq('p.id','i.project_id'))->leftJoin('i','re_erp_orders','o',$q->expr()->eq('o.id','i.order_id'))->where($q->expr()->eq('i.id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();return $r?:null;
 }
 private function invoiceCompany(array $invoice):array{
  $snapshot=trim((string)($invoice['company_snapshot']??''));if($snapshot!==''){$data=json_decode($snapshot,true);if(is_array($data))return $data;}return $this->companyData();
 }
 private function invoiceCustomer(array $invoice):array{
  $snapshot=trim((string)($invoice['customer_snapshot']??''));if($snapshot!==''){$data=json_decode($snapshot,true);if(is_array($data))return $data;}return $this->one('re_erp_customers',(int)$invoice['customer_id'])??[];
 }
 private function ordersRows():array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no')->from('re_erp_orders','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->orderBy('o.order_date','DESC');return $q->executeQuery()->fetchAllAssociative();}
 private function order(int $id):?array{$q=$this->db->getQueryBuilder();$q->select('o.*','c.name AS customer_name','p.project_no','p.title AS project_title')->from('re_erp_orders','o')->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))->where($q->expr()->eq('o.id',$q->createNamedParameter($id)));$r=$q->executeQuery()->fetchAssociative();if(!$r)return null;return $r;}
 private function projectNotes(int $projectId):array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')
   ->from('re_erp_order_notes')
   ->where($q->expr()->eq('project_id',$q->createNamedParameter($projectId)))
   ->orderBy('created_at','DESC')
   ->addOrderBy('id','DESC');
  return $q->executeQuery()->fetchAllAssociative();
 }

 private function orderNotes(int $orderId):array{
	$q=$this->db->getQueryBuilder();
	$q->select('*')
		->from('re_erp_order_notes')
		->where($q->expr()->eq('order_id',$q->createNamedParameter($orderId)))
		->orderBy('created_at','DESC')
		->addOrderBy('id','DESC');
	return $q->executeQuery()->fetchAllAssociative();
}
 private function materialsWithMeta():array{$q=$this->db->getQueryBuilder();$q->select('m.*','g.name AS group_name','s.name AS supplier_name')->from('re_erp_materials','m')->leftJoin('m','re_erp_material_groups','g',$q->expr()->eq('g.id','m.material_group_id'))->leftJoin('m','re_erp_suppliers','s',$q->expr()->eq('s.id','m.supplier_id'))->orderBy('m.name','ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function stockRows():array{$q=$this->db->getQueryBuilder();$q->select('s.*','m.article_no','m.name AS material_name','m.unit','p.project_no')->from('re_erp_stock_movements','s')->leftJoin('s','re_erp_materials','m',$q->expr()->eq('m.id','s.material_id'))->leftJoin('s','re_erp_projects','p',$q->expr()->eq('p.id','s.project_id'))->orderBy('s.created_at','DESC')->setMaxResults(100);return $q->executeQuery()->fetchAllAssociative();}
 private function activeTimer(string $uid):?array{$q=$this->db->getQueryBuilder();$q->select('t.*','p.project_no','p.title')->from('re_erp_time_timers','t')->leftJoin('t','re_erp_projects','p',$q->expr()->eq('p.id','t.project_id'))->where($q->expr()->eq('t.user_id',$q->createNamedParameter($uid)))->andWhere($q->expr()->in('t.status',[$q->createNamedParameter('running'),$q->createNamedParameter('paused')]))->orderBy('t.id','DESC')->setMaxResults(1);$r=$q->executeQuery()->fetchAssociative();return $r?:null;}
 private function todayHours(string $uid):float{$q=$this->db->getQueryBuilder();$q->select($q->func()->sum('e.hours','s'))->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->where($q->expr()->eq('w.user_id',$q->createNamedParameter($uid)))->andWhere($q->expr()->eq('w.work_date',$q->createNamedParameter(date('Y-m-d'))));return (float)($q->executeQuery()->fetchOne()?:0);}
 private function recentEntries(string $uid):array{$q=$this->db->getQueryBuilder();$q->select('e.*','w.work_date','p.project_no','p.title')->from('re_erp_workday_entries','e')->innerJoin('e','re_erp_workdays','w',$q->expr()->eq('w.id','e.workday_id'))->leftJoin('e','re_erp_projects','p',$q->expr()->eq('p.id','e.project_id'))->where($q->expr()->eq('w.user_id',$q->createNamedParameter($uid)))->orderBy('w.work_date','DESC')->addOrderBy('e.id','DESC')->setMaxResults(10);return $q->executeQuery()->fetchAllAssociative();}

 private function positionImages(int $count,array $existing=[]):array{
  $upload=$this->request->getUploadedFile('itemImages');$keep=$this->request->getParam('keepImages',[]);$out=[];
  for($i=0;$i<$count;$i++){
   $file=null;
   if(is_array($upload)&&isset($upload['name'])&&is_array($upload['name'])){$file=['name'=>$upload['name'][$i]??'','type'=>$upload['type'][$i]??'','tmp_name'=>$upload['tmp_name'][$i]??'','error'=>$upload['error'][$i]??UPLOAD_ERR_NO_FILE,'size'=>$upload['size'][$i]??0];}
   elseif(is_array($upload)&&isset($upload[$i])&&is_array($upload[$i]))$file=$upload[$i];
   if(is_array($file)&&($file['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_OK){
    if((int)($file['size']??0)>5*1024*1024)throw new \InvalidArgumentException('Positionsbilder dürfen maximal 5 MB groß sein.');
    $tmp=(string)($file['tmp_name']??'');$info=$tmp!==''?@getimagesize($tmp):false;$mime=is_array($info)?(string)($info['mime']??''):'';
    if(!in_array($mime,['image/jpeg','image/png'],true))throw new \InvalidArgumentException('Positionsbilder müssen JPG oder PNG sein.');
    $raw=@file_get_contents($tmp);if($raw===false)throw new \InvalidArgumentException('Positionsbild konnte nicht gelesen werden.');
    $out[$i]=['image_name'=>basename((string)($file['name']??'Bild')),'image_mime'=>$mime,'image_data'=>base64_encode($raw)];continue;
   }
   if(!empty($keep[$i])&&!empty($existing[$i]['image_data']))$out[$i]=['image_name'=>$existing[$i]['image_name']??null,'image_mime'=>$existing[$i]['image_mime']??null,'image_data'=>$existing[$i]['image_data']??null];
   else $out[$i]=['image_name'=>null,'image_mime'=>null,'image_data'=>null];
  }
  return $out;
 }

 private function insert(string $table,array $data):int{
  $q=$this->db->getQueryBuilder();
  $q->insert($table);
  foreach($data as $column=>$value){
   if(is_bool($value)){
    $value=$value?1:0;
   }
   $q->setValue($column,$q->createNamedParameter($value));
  }
  $q->executeStatement();
  return (int)$q->getLastInsertId();
 }

 private function update(string $table,int $id,array $data):void{
  $q=$this->db->getQueryBuilder();
  $q->update($table);
  foreach($data as $column=>$value){
   $q->set($column,$q->createNamedParameter($value));
  }
  $q->where($q->expr()->eq('id',$q->createNamedParameter($id)));
  $q->executeStatement();
 }

 private function rows(string $table,string $orderBy='id',string $direction='DESC'):array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from($table)->orderBy($orderBy,strtoupper($direction)==='ASC'?'ASC':'DESC');
  return $q->executeQuery()->fetchAllAssociative();
 }
 private function where(string $table,string $column,mixed $value,string $orderBy='id',string $direction='ASC'):array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from($table)
    ->where($q->expr()->eq($column,$q->createNamedParameter($value)))
    ->orderBy($orderBy,strtoupper($direction)==='DESC'?'DESC':'ASC');
  return $q->executeQuery()->fetchAllAssociative();
 }

 private function communications():array{
  $q=$this->db->getQueryBuilder();
  $q->select('c.*','cu.name AS customer_name','p.project_no','p.title AS project_title')
    ->from('re_erp_communications','c')
    ->leftJoin('c','re_erp_customers','cu',$q->expr()->eq('cu.id','c.customer_id'))
    ->leftJoin('c','re_erp_projects','p',$q->expr()->eq('p.id','c.project_id'))
    ->orderBy('c.contact_at','DESC')
    ->addOrderBy('c.id','DESC')
    ->setMaxResults(100);
  return $q->executeQuery()->fetchAllAssociative();
 }

 private function dueFollowUps():array{
  $q=$this->db->getQueryBuilder();
  $q->select('c.*','cu.name AS customer_name','p.project_no','p.title AS project_title')
    ->from('re_erp_communications','c')
    ->leftJoin('c','re_erp_customers','cu',$q->expr()->eq('cu.id','c.customer_id'))
    ->leftJoin('c','re_erp_projects','p',$q->expr()->eq('p.id','c.project_id'))
    ->where($q->expr()->isNotNull('c.follow_up_at'))
    ->andWhere($q->expr()->lte('c.follow_up_at',$q->createNamedParameter(date('Y-m-d H:i:s'))))
    ->orderBy('c.follow_up_at','ASC')
    ->addOrderBy('c.id','ASC');
  return $q->executeQuery()->fetchAllAssociative();
 }

 private function dt(?string $value):?string{
  $value=trim((string)$value);
  if($value==='')return null;
  $ts=strtotime($value);
  if($ts===false)throw new \InvalidArgumentException('Ungültiges Datum oder ungültige Uhrzeit.');
  return date('Y-m-d H:i:s',$ts);
 }

 private function oneBy(string $table,string $column,mixed $value):?array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from($table)
    ->where($q->expr()->eq($column,$q->createNamedParameter($value)))
    ->setMaxResults(1);
  $row=$q->executeQuery()->fetchAssociative();
  return $row?:null;
 }

 private function one(string $table,int $id):?array{
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from($table)
    ->where($q->expr()->eq('id',$q->createNamedParameter($id)))
    ->setMaxResults(1);
  $row=$q->executeQuery()->fetchAssociative();
  return $row?:null;
 }

 private function offersRows():array{
  $q=$this->db->getQueryBuilder();
  $q->select('o.*','c.name AS customer_name','p.project_no','p.title AS project_title')
    ->from('re_erp_offers','o')
    ->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))
    ->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))
    ->orderBy('o.offer_date','DESC')->addOrderBy('o.id','DESC');
  return $q->executeQuery()->fetchAllAssociative();
 }

 private function offer(int $id):?array{
  $q=$this->db->getQueryBuilder();
  $q->select('o.*','c.name AS customer_name','p.project_no','p.title AS project_title')
    ->from('re_erp_offers','o')
    ->leftJoin('o','re_erp_customers','c',$q->expr()->eq('c.id','o.customer_id'))
    ->leftJoin('o','re_erp_projects','p',$q->expr()->eq('p.id','o.project_id'))
    ->where($q->expr()->eq('o.id',$q->createNamedParameter($id)))
    ->setMaxResults(1);
  $row=$q->executeQuery()->fetchAssociative();
  return $row?:null;
 }

 private function previousInstallments(array $invoice):array{
  $orderId=(int)($invoice['order_id']??0);
  if($orderId<=0)return [];
  $q=$this->db->getQueryBuilder();
  $q->select('*')->from('re_erp_invoices')
    ->where($q->expr()->eq('order_id',$q->createNamedParameter($orderId)))
    ->andWhere($q->expr()->eq('invoice_type',$q->createNamedParameter('advance')))
    ->andWhere($q->expr()->neq('status',$q->createNamedParameter('cancelled')))
    ->andWhere($q->expr()->lt('id',$q->createNamedParameter((int)($invoice['id']??PHP_INT_MAX))))
    ->orderBy('id','ASC');
  return $q->executeQuery()->fetchAllAssociative();
 }
}
