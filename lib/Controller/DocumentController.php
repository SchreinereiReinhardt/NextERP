<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Controller;
use OCA\ReinhardtERP\Service\DocumentInboxService;
use OCA\ReinhardtERP\Service\FolderService;
use OCA\ReinhardtERP\Service\PermissionService;
use OCA\ReinhardtERP\Service\DocumentRuleService;
use OCA\ReinhardtERP\Service\DocumentOfferImportService;
use OCA\ReinhardtERP\Service\DocumentPdfOfferExtractorService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IURLGenerator;
final class DocumentController extends Controller {
 public function __construct(string $appName,IRequest $request,private DocumentInboxService $documents,private FolderService $folders,private PermissionService $permissions,private IDBConnection $db,private IURLGenerator $url,private DocumentRuleService $rules,private DocumentOfferImportService $offerImport,private DocumentPdfOfferExtractorService $offerExtractor){parent::__construct($appName,$request);}
 #[NoAdminRequired,NoCSRFRequired] public function index(string $status='all',string $type='all',string $q='',string $processing='all',string $year='',string $month='',?int $supplierId=null,?int $customerId=null,?int $projectId=null,string $view='overview'):TemplateResponse{$this->permissions->assert('documents');$this->documents->ensureStructure();return new TemplateResponse($this->appName,'document_inbox',['documents'=>$this->documents->rows($status,$type,$q,$processing,$year,$month,(int)$supplierId,(int)$customerId,(int)$projectId),'counts'=>$this->documents->counts(),'status'=>$status,'type'=>$type,'q'=>$q,'processing'=>$processing,'year'=>$year,'month'=>$month,'supplierId'=>(int)$supplierId,'customerId'=>(int)$customerId,'projectId'=>(int)$projectId,'view'=>$view,'suppliers'=>$this->documents->lookupRows('re_erp_suppliers','name'),'customers'=>$this->documents->lookupRows('re_erp_customers','name'),'projects'=>$this->documents->lookupRows('re_erp_projects','project_no'),'scanInfo'=>$this->documents->scanInfo(),'rules'=>$this->rules->all(),'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error','')]);}
 #[NoAdminRequired,NoCSRFRequired] public function finance(string $type='all',string $q='',string $year='',string $month='',?int $supplierId=null,?int $customerId=null,?int $projectId=null,string $metricPeriod='year'):TemplateResponse{
  $this->permissions->assert('documents');
  $this->documents->ensureStructure();
  $filters=['type'=>$type,'q'=>$q,'year'=>$year,'month'=>$month,'supplier_id'=>$supplierId,'customer_id'=>$customerId,'project_id'=>$projectId];
  return new TemplateResponse($this->appName,'finance',[
   'documents'=>$this->documents->financeRows($filters),
   'counts'=>$this->documents->financeCounts($filters),
   'type'=>$type,'q'=>$q,'year'=>$year,'month'=>$month,
   'supplierId'=>(int)$supplierId,'customerId'=>(int)$customerId,'projectId'=>(int)$projectId,
   'suppliers'=>$this->documents->lookupRows('re_erp_suppliers','name'),
   'customers'=>$this->documents->lookupRows('re_erp_customers','name'),
   'projects'=>$this->documents->lookupRows('re_erp_projects','project_no'),
   'financeDashboard'=>$this->financeDashboard($metricPeriod),
   'metricPeriod'=>$metricPeriod,
   'message'=>(string)$this->request->getParam('message',''),
   'error'=>(string)$this->request->getParam('error',''),
  ]);
 }
 #[NoAdminRequired,NoCSRFRequired] public function bankStatements(string $q='',string $year='',string $month=''):TemplateResponse{
  return $this->financeSection('finance_bank_statements','bank_statement',$q,$year,$month);
 }
 #[NoAdminRequired,NoCSRFRequired] public function cashbook(string $q='',string $year='',string $month=''):TemplateResponse{
  $this->permissions->assert('documents');$this->documents->ensureStructure();
  $qb=$this->db->getQueryBuilder();$qb->select('*')->from('re_erp_cash_entries')->orderBy('entry_date','ASC')->addOrderBy('id','ASC');$entries=$qb->executeQuery()->fetchAll();
  $balance=0.0;$income=0.0;$expense=0.0;foreach($entries as &$e){if(!empty($e['cancelled_at'])){$e['running_balance']=$balance;continue;}$amount=(float)$e['amount'];$type=(string)$e['entry_type'];if($type==='expense'){$balance-=$amount;$expense+=$amount;}else{$balance+=$amount;if($type==='income')$income+=$amount;}$e['running_balance']=$balance;}unset($e);
  $entries=array_reverse($entries);
  return new TemplateResponse($this->appName,'finance_cash',['entries'=>$entries,'balance'=>$balance,'income'=>$income,'expense'=>$expense,'count'=>count($entries),'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error','')]);
 }
 #[NoAdminRequired] public function cashAdd(string $entryType,float $amount,string $description,?string $entryDate=null,?float $vatRate=null,?string $category=null,?string $receiptNo=null):RedirectResponse{
  $this->permissions->assert('documents');$entryType=in_array($entryType,['income','expense','opening'],true)?$entryType:'';if($entryType===''||$amount<=0||trim($description)==='')return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.cashbook',['error'=>'Bitte Art, Betrag und Beschreibung vollständig angeben.']));
  $date=$entryDate?:date('Y-m-d');if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$date=date('Y-m-d');$vat=$vatRate===null?null:max(0,min(100,$vatRate));
  $qb=$this->db->getQueryBuilder();$qb->insert('re_erp_cash_entries')->values(['entry_date'=>$qb->createNamedParameter($date),'entry_type'=>$qb->createNamedParameter($entryType),'amount'=>$qb->createNamedParameter(round($amount,2)),'vat_rate'=>$qb->createNamedParameter($vat),'category'=>$qb->createNamedParameter(trim((string)$category)?:null),'description'=>$qb->createNamedParameter(trim($description)),'receipt_no'=>$qb->createNamedParameter(trim((string)$receiptNo)?:null),'created_at'=>$qb->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();
  return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.cashbook',['message'=>'Kassenbuchung gespeichert.']));
 }
 #[NoAdminRequired] public function cashCancel(int $id,string $reason):RedirectResponse{
  $this->permissions->assert('documents');if(trim($reason)==='')return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.cashbook',['error'=>'Für ein Storno ist ein Grund erforderlich.']));
  $qb=$this->db->getQueryBuilder();$qb->update('re_erp_cash_entries')->set('cancelled_at',$qb->createNamedParameter(date('Y-m-d H:i:s')))->set('cancel_reason',$qb->createNamedParameter(trim($reason)))->where($qb->expr()->eq('id',$qb->createNamedParameter($id)))->andWhere($qb->expr()->isNull('cancelled_at'))->executeStatement();
  return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.cashbook',['message'=>'Kassenbuchung storniert; der ursprüngliche Eintrag bleibt nachvollziehbar erhalten.']));
 }
 #[NoAdminRequired,NoCSRFRequired] public function taxes(string $q='',string $year='',string $month=''):TemplateResponse{
  $this->permissions->assert('documents');$this->documents->ensureStructure();$year=preg_match('/^\d{4}$/',$year)?$year:date('Y');$from=$year.'-01-01';$to=$year.'-12-31';if(preg_match('/^(0[1-9]|1[0-2])$/',$month)){$from=$year.'-'.$month.'-01';$to=date('Y-m-t',strtotime($from));}
  $qb=$this->db->getQueryBuilder();$qb->select('invoice_type','status','net_amount','gross_amount','vat_rate','finalized_at')->from('re_erp_invoices')->where($qb->expr()->gte('invoice_date',$qb->createNamedParameter($from)))->andWhere($qb->expr()->lte('invoice_date',$qb->createNamedParameter($to)))->andWhere($qb->expr()->isNotNull('finalized_at'));$rows=$qb->executeQuery()->fetchAll();
  $tax=['net'=>0.0,'gross'=>0.0,'vat'=>0.0,'rates'=>['19'=>0.0,'7'=>0.0,'0'=>0.0],'count'=>0];foreach($rows as $r){if(($r['status']??'')==='cancelled')continue;$sign=(($r['invoice_type']??'')==='credit')?-1:1;$net=$sign*(float)$r['net_amount'];$gross=$sign*(float)$r['gross_amount'];$vat=$gross-$net;$tax['net']+=$net;$tax['gross']+=$gross;$tax['vat']+=$vat;$rate=(float)($r['vat_rate']??0);$key=$rate>=18.5?'19':($rate>=6.5?'7':'0');$tax['rates'][$key]+=$vat;$tax['count']++;}
  $filters=['type'=>'tax','q'=>$q,'year'=>$year,'month'=>$month];$docs=$this->documents->financeRows($filters);return new TemplateResponse($this->appName,'finance_taxes',['documents'=>$docs,'count'=>count($docs),'tax'=>$tax,'year'=>$year,'month'=>$month,'from'=>$from,'to'=>$to,'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error','')]);
 }
 private function financeSection(string $template,string $type,string $q,string $year,string $month):TemplateResponse{
  $this->permissions->assert('documents');
  $this->documents->ensureStructure();
  $filters=['type'=>$type,'q'=>$q,'year'=>$year,'month'=>$month];
  $rows=$this->documents->financeRows($filters);
  return new TemplateResponse($this->appName,$template,[
   'documents'=>$rows,'count'=>count($rows),'q'=>$q,'year'=>$year,'month'=>$month,
   'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error',''),
  ]);
 }
 private function financeDashboard(string $period):array{
  $allowed=['month','quarter','year','previous_year'];if(!in_array($period,$allowed,true))$period='year';
  $today=new \DateTimeImmutable('today');
  if($period==='month'){$start=$today->modify('first day of this month');$end=$today->modify('last day of this month');$label=$start->format('m/Y');}
  elseif($period==='quarter'){$m=(int)$today->format('n');$qm=(int)(floor(($m-1)/3)*3+1);$start=$today->setDate((int)$today->format('Y'),$qm,1);$end=$start->modify('+2 months')->modify('last day of this month');$label='Q'.(int)ceil($m/3).' '.$today->format('Y');}
  elseif($period==='previous_year'){$y=(int)$today->format('Y')-1;$start=new \DateTimeImmutable($y.'-01-01');$end=new \DateTimeImmutable($y.'-12-31');$label=(string)$y;}
  else{$start=new \DateTimeImmutable($today->format('Y').'-01-01');$end=new \DateTimeImmutable($today->format('Y').'-12-31');$label=$today->format('Y');}
  $q=$this->db->getQueryBuilder();$q->select('i.id','i.invoice_no','i.customer_id','i.project_id','i.invoice_date','i.due_date','i.status','i.invoice_type','i.gross_amount','i.net_amount','i.advance_gross_amount','c.name AS customer_name','p.project_no')
   ->from('re_erp_invoices','i')->leftJoin('i','re_erp_customers','c',$q->expr()->eq('c.id','i.customer_id'))->leftJoin('i','re_erp_projects','p',$q->expr()->eq('p.id','i.project_id'))
   ->where($q->expr()->gte('i.invoice_date',$q->createNamedParameter($start->format('Y-m-d'))))->andWhere($q->expr()->lte('i.invoice_date',$q->createNamedParameter($end->format('Y-m-d'))))->orderBy('i.invoice_date','DESC');$invoices=$q->executeQuery()->fetchAll();
  $paymentByInvoice=[];$paymentsTotal=0.0;$q=$this->db->getQueryBuilder();$q->select('p.invoice_id','p.amount','p.payment_date')->from('re_erp_invoice_payments','p')->where($q->expr()->gte('p.payment_date',$q->createNamedParameter($start->format('Y-m-d'))))->andWhere($q->expr()->lte('p.payment_date',$q->createNamedParameter($end->format('Y-m-d'))));foreach($q->executeQuery()->fetchAll() as $x){$paymentsTotal+=(float)$x['amount'];}
  $ids=array_fill_keys(array_map(static fn(array $x):int=>(int)$x['id'],$invoices),true);if($ids){$q=$this->db->getQueryBuilder();$q->select('invoice_id','amount')->from('re_erp_invoice_payments');foreach($q->executeQuery()->fetchAll() as $x){$iid=(int)$x['invoice_id'];if(isset($ids[$iid]))$paymentByInvoice[$iid]=($paymentByInvoice[$iid]??0)+(float)$x['amount'];}}
  $gross=0.0;$net=0.0;$open=0.0;$overdue=0.0;$openRows=[];$monthly=[];$customers=[];$status=['draft'=>0,'open'=>0,'partial'=>0,'paid'=>0,'overdue'=>0,'cancelled'=>0,'credit'=>0];
  foreach($invoices as $i){$st=(string)$i['status'];$type=(string)($i['invoice_type']??'invoice');if($st==='cancelled'){$status['cancelled']++;continue;}if($st==='draft'){$status['draft']++;continue;}$amount=(float)$i['gross_amount'];$netAmount=(float)$i['net_amount'];if($type==='final')$amount-=(float)($i['advance_gross_amount']??0);$gross+=$amount;$net+=$netAmount;$month=substr((string)$i['invoice_date'],0,7);$monthly[$month]=($monthly[$month]??0)+$amount;$customer=(string)($i['customer_name']??'Ohne Kunde');$customers[$customer]=($customers[$customer]??0)+$amount;if($type==='credit')$status['credit']++;
   $paid=(float)($paymentByInvoice[(int)$i['id']]??0);$rest=max(0,round($amount-$paid,2));$isOver=$rest>0&&!empty($i['due_date'])&&(string)$i['due_date']<$today->format('Y-m-d');if($rest<=0.009){$status['paid']++;}elseif($isOver){$status['overdue']++;$overdue+=$rest;}elseif($paid>0){$status['partial']++;}else{$status['open']++;}if($rest>0){$open+=$rest;$i['paid_amount']=$paid;$i['open_amount']=$rest;$i['days_overdue']=$isOver?max(0,(int)(new \DateTimeImmutable((string)$i['due_date']))->diff($today)->format('%a')):0;$openRows[]=$i;}
  }
  // Offene Forderungen sind eine Stichtagsgröße und werden deshalb unabhängig vom gewählten Umsatzzeitraum vollständig betrachtet.
  $open=0.0;$overdue=0.0;$openRows=[];$q=$this->db->getQueryBuilder();$q->select('i.id','i.invoice_no','i.invoice_date','i.due_date','i.status','i.invoice_type','i.gross_amount','i.advance_gross_amount','c.name AS customer_name','p.project_no')->from('re_erp_invoices','i')->leftJoin('i','re_erp_customers','c',$q->expr()->eq('c.id','i.customer_id'))->leftJoin('i','re_erp_projects','p',$q->expr()->eq('p.id','i.project_id'))->where($q->expr()->neq('i.status',$q->createNamedParameter('draft')))->andWhere($q->expr()->neq('i.status',$q->createNamedParameter('cancelled')));$receivables=$q->executeQuery()->fetchAll();
  $allIds=array_fill_keys(array_map(static fn(array $x):int=>(int)$x['id'],$receivables),true);$allPaid=[];if($allIds){$q=$this->db->getQueryBuilder();$q->select('invoice_id','amount')->from('re_erp_invoice_payments');foreach($q->executeQuery()->fetchAll() as $x){$iid=(int)$x['invoice_id'];if(isset($allIds[$iid]))$allPaid[$iid]=($allPaid[$iid]??0)+(float)$x['amount'];}}
  foreach($receivables as $i){$amount=(float)$i['gross_amount'];if((string)($i['invoice_type']??'invoice')==='final')$amount-=(float)($i['advance_gross_amount']??0);if($amount<=0)continue;$paid=(float)($allPaid[(int)$i['id']]??0);$rest=max(0,round($amount-$paid,2));if($rest<=0.009)continue;$isOver=!empty($i['due_date'])&&(string)$i['due_date']<$today->format('Y-m-d');$open+=$rest;if($isOver)$overdue+=$rest;$i['paid_amount']=$paid;$i['open_amount']=$rest;$i['days_overdue']=$isOver?max(0,(int)(new \DateTimeImmutable((string)$i['due_date']))->diff($today)->format('%a')):0;$openRows[]=$i;}
  usort($openRows,static fn(array $a,array $b):int=>($b['days_overdue']<=>$a['days_overdue'])?:strcmp((string)($a['due_date']??'9999'),(string)($b['due_date']??'9999')));$openRows=array_slice($openRows,0,12);arsort($customers);$top=[];foreach(array_slice($customers,0,5,true) as $name=>$value)$top[]=['name'=>$name,'amount'=>$value];ksort($monthly);
  return ['period'=>$period,'label'=>$label,'from'=>$start->format('d.m.Y'),'to'=>$end->format('d.m.Y'),'gross'=>round($gross,2),'net'=>round($net,2),'payments'=>round($paymentsTotal,2),'open'=>round($open,2),'overdue'=>round($overdue,2),'invoices'=>count($invoices),'status'=>$status,'openRows'=>$openRows,'monthly'=>$monthly,'topCustomers'=>$top];
 }
 #[NoAdminRequired,NoCSRFRequired] public function financeExport(string $type='all',string $q='',string $year='',string $month='',?int $supplierId=null,?int $customerId=null,?int $projectId=null):DataDisplayResponse{
  $this->permissions->assert('documents');
  $filters=['type'=>$type,'q'=>$q,'year'=>$year,'month'=>$month,'supplier_id'=>$supplierId,'customer_id'=>$customerId,'project_id'=>$projectId];
  $export=$this->documents->financeExport($filters);
  $response=new DataDisplayResponse($export['content'],200,['Content-Type'=>'application/zip','Content-Disposition'=>'attachment; filename="'.$export['name'].'"']);
  return $response;
 }
 #[NoAdminRequired,NoCSRFRequired] public function review(int $id):TemplateResponse{return $this->renderDetail($id);}
 #[NoAdminRequired,NoCSRFRequired] public function detail(int $id):TemplateResponse{return $this->renderDetail($id);}
 #[NoAdminRequired,NoCSRFRequired]
 public function preview(int $id):DataDisplayResponse|NotFoundResponse {
  $this->permissions->assert('documents');
  $doc = $this->documents->get($id);
  if (!$doc) {
   return new NotFoundResponse();
  }
  try {
   $file = $this->folders->readFile((string)$doc['file_path']);
  } catch (\Throwable) {
   return new NotFoundResponse();
  }
  $name = str_replace(['"', "\r", "\n"], '', (string)$file['name']);
  $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $mime = (string)($file['mime'] ?: 'application/octet-stream');
  if ($extension === 'pdf') {
   $mime = 'application/pdf';
  } elseif (in_array($extension, ['jpg', 'jpeg'], true)) {
   $mime = 'image/jpeg';
  } elseif ($extension === 'png') {
   $mime = 'image/png';
  } elseif ($extension === 'webp') {
   $mime = 'image/webp';
  }
  $content = (string)$file['content'];
  return new DataDisplayResponse($content, 200, [
   'Content-Type' => $mime,
   'Content-Length' => (string)strlen($content),
   'Content-Disposition' => 'inline; filename="'.$name.'"',
   'X-Content-Type-Options' => 'nosniff',
   'Cache-Control' => 'private, no-store, max-age=0',
  ]);
 }
 #[NoAdminRequired,NoCSRFRequired]
 public function previewImage(int $id):DataDisplayResponse|NotFoundResponse {
  $this->permissions->assert('documents');
  $doc = $this->documents->get($id);
  if (!$doc) {
   return new NotFoundResponse();
  }
  try {
   $file = $this->folders->readFile((string)$doc['file_path']);
  } catch (\Throwable) {
   return new NotFoundResponse();
  }
  $name = (string)$file['name'];
  $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  $mime = (string)($file['mime'] ?? 'application/octet-stream');
  $content = (string)$file['content'];
  if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg','jpeg','png','webp'], true)) {
   $imageMime = match ($extension) {
    'jpg', 'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    default => $mime,
   };
   return new DataDisplayResponse($content, 200, [
    'Content-Type' => $imageMime,
    'Content-Length' => (string)strlen($content),
    'Content-Disposition' => 'inline; filename="preview.'.($extension ?: 'img').'"',
    'X-Content-Type-Options' => 'nosniff',
    'Cache-Control' => 'private, no-store, max-age=0',
   ]);
  }
  if ($extension !== 'pdf' && $mime !== 'application/pdf') {
   return new NotFoundResponse();
  }
  $base = tempnam(sys_get_temp_dir(), 'nexterp_pdf_');
  if ($base === false) {
   return new NotFoundResponse();
  }
  $pdfPath = $base.'.pdf';
  $pngBase = $base.'_page';
  @unlink($base);
  try {
   if (file_put_contents($pdfPath, $content) === false) {
    return new NotFoundResponse();
   }
   $command = ['pdftoppm', '-f', '1', '-singlefile', '-png', '-r', '130', '-scale-to-x', '1500', '-scale-to-y', '-1', $pdfPath, $pngBase];
   $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
   $process = proc_open($command, $descriptor, $pipes);
   if (!is_resource($process)) {
    return new NotFoundResponse();
   }
   stream_get_contents($pipes[1]);
   fclose($pipes[1]);
   stream_get_contents($pipes[2]);
   fclose($pipes[2]);
   $exitCode = proc_close($process);
   $pngPath = $pngBase.'.png';
   if ($exitCode !== 0 || !is_file($pngPath)) {
    return new NotFoundResponse();
   }
   $png = file_get_contents($pngPath);
   if ($png === false) {
    return new NotFoundResponse();
   }
   return new DataDisplayResponse($png, 200, [
    'Content-Type' => 'image/png',
    'Content-Length' => (string)strlen($png),
    'Content-Disposition' => 'inline; filename="preview.png"',
    'X-Content-Type-Options' => 'nosniff',
    'Cache-Control' => 'private, no-store, max-age=0',
   ]);
  } finally {
   @unlink($pdfPath);
   @unlink($pngBase.'.png');
  }
 }
 #[NoAdminRequired] public function upload():RedirectResponse{$this->permissions->assert('documents');try{$file=$this->request->getUploadedFile('document');if(!is_array($file))throw new \InvalidArgumentException('Bitte eine Datei auswählen.');$id=$this->documents->upload($file);return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id]));}catch(\Throwable $e){return $this->goError($e->getMessage());}}
 #[NoAdminRequired] public function scan():RedirectResponse{$this->permissions->assert('documents');try{$count=$this->documents->syncInbox();return $this->go($count.' neue Datei(en) eingelesen.');}catch(\Throwable $e){return $this->goError($e->getMessage());}}
 #[NoAdminRequired] public function analyse(int $id):RedirectResponse{$this->permissions->assert('documents');try{$this->documents->analyse($id);return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'message'=>'Dokument wurde erneut analysiert.']));}catch(\Throwable $e){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'error'=>$e->getMessage()]));}}
 #[NoAdminRequired] public function assign(int $id,string $documentType,?int $customerId=null,?int $projectId=null,?int $orderId=null,?int $supplierId=null,?int $projectSupplierId=null,?string $supplierDocumentRole=null,?string $documentNo=null,?string $documentDate=null,?string $dueDate=null,mixed $netAmount=null,mixed $vatAmount=null,mixed $grossAmount=null,string $currency='EUR',?string $notes=null):RedirectResponse{$this->permissions->assert('documents');try{
  $cockpit=null;$role=in_array($supplierDocumentRole,['confirmation','delivery'],true)?$supplierDocumentRole:null;
  if($projectSupplierId){$q=$this->db->getQueryBuilder();$q->select('ps.*','s.name AS supplier_name')->from('re_erp_project_suppliers','ps')->leftJoin('ps','re_erp_suppliers','s',$q->expr()->eq('s.id','ps.supplier_id'))->where($q->expr()->eq('ps.id',$q->createNamedParameter($projectSupplierId)));$cockpit=$q->executeQuery()->fetch();if(!$cockpit)throw new \InvalidArgumentException('Der ausgewählte Lieferanten-/Bestellvorgang wurde nicht gefunden.');if(!$projectId)$projectId=(int)$cockpit['project_id'];if((int)$cockpit['project_id']!==(int)$projectId)throw new \InvalidArgumentException('Der Lieferantenvorgang gehört nicht zum ausgewählten Projekt.');if(!$supplierId)$supplierId=(int)$cockpit['supplier_id'];if((int)$cockpit['supplier_id']>0&&(int)$supplierId>0&&(int)$cockpit['supplier_id']!==(int)$supplierId)throw new \InvalidArgumentException('Der Lieferantenvorgang gehört nicht zum ausgewählten Lieferanten.');if(!$role)throw new \InvalidArgumentException('Bitte auswählen, ob das Dokument als Auftragsbestätigung oder Lieferschein verknüpft werden soll.');}
  $this->documents->assign($id,['document_type'=>$documentType,'customer_id'=>$customerId,'project_id'=>$projectId,'order_id'=>$orderId,'supplier_id'=>$supplierId,'document_no'=>$documentNo,'document_date'=>$documentDate,'due_date'=>$dueDate,'net_amount'=>$netAmount,'vat_amount'=>$vatAmount,'gross_amount'=>$grossAmount,'currency'=>$currency,'notes'=>$notes]);
  if($cockpit&&$role){$field=$role==='confirmation'?'confirmation_document_id':'delivery_document_id';$q=$this->db->getQueryBuilder();$q->update('re_erp_project_suppliers')->set($field,$q->createNamedParameter($id))->set('updated_at',$q->createNamedParameter(date('Y-m-d H:i:s')));if($role==='confirmation')$q->set('confirmation_status',$q->createNamedParameter('received'));$q->where($q->expr()->eq('id',$q->createNamedParameter($projectSupplierId)))->executeStatement();}
  if($documentType==='offer'){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'message'=>'Dokument wurde zugeordnet. Du kannst es jetzt als ERP-Angebot übernehmen.']));}return $this->go($cockpit?'Dokument wurde zugeordnet und mit dem Lieferantenvorgang verknüpft.':'Dokument wurde zugeordnet, umbenannt und abgelegt.');}catch(\Throwable $e){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'error'=>$e->getMessage()]));}}
 #[NoAdminRequired] public function createRule(string $name,string $matchValue,?string $documentType=null,?int $customerId=null,?int $projectId=null,?int $orderId=null,?int $supplierId=null,int $priority=100):RedirectResponse{$this->permissions->assert('documents');$this->rules->create(['name'=>$name,'match_value'=>$matchValue,'document_type'=>$documentType,'customer_id'=>$customerId,'project_id'=>$projectId,'order_id'=>$orderId,'supplier_id'=>$supplierId,'priority'=>$priority]);return $this->go('Dokumentenregel wurde angelegt.');}
 #[NoAdminRequired] public function deleteRule(int $id):RedirectResponse{$this->permissions->assert('documents');$this->rules->delete($id);return $this->go('Dokumentenregel wurde gelöscht.');}
 #[NoAdminRequired] public function importOffer(int $id,?int $customerId=null,?int $projectId=null,string $title='',string $description='',?string $offerDate=null,?string $validUntil=null,mixed $netAmount=null,mixed $vatAmount=null,mixed $grossAmount=null,mixed $vatRate=null,?string $notes=null,bool $createProject=false,?string $projectTitle=null,array $positionDescription=[],array $positionQuantity=[],array $positionUnit=[],array $positionUnitPrice=[],array $positionTotal=[]):RedirectResponse{$this->permissions->assert('offers');try{$result=$this->offerImport->importOffer($id,['customer_id'=>$customerId,'project_id'=>$projectId,'title'=>$title,'description'=>$description,'offer_date'=>$offerDate,'valid_until'=>$validUntil,'net_amount'=>$netAmount,'vat_amount'=>$vatAmount,'gross_amount'=>$grossAmount,'vat_rate'=>$vatRate,'notes'=>$notes,'create_project'=>$createProject,'project_title'=>$projectTitle,'positions'=>$this->normaliseOfferPositions($positionDescription,$positionQuantity,$positionUnit,$positionUnitPrice,$positionTotal)]);return new RedirectResponse($this->url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$result['offer_id']]));}catch(\Throwable $e){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'error'=>$e->getMessage()]));}}
 private function renderDetail(int $id):TemplateResponse{
  $this->permissions->assert('documents');
  $doc=$this->documents->get($id);
  if(!$doc)return new TemplateResponse($this->appName,'document_detail',['missing'=>true,'urlGenerator'=>$this->url]);
  $customers=$this->rows('re_erp_customers','name');$projects=$this->rows('re_erp_projects','project_no');$extracted=$this->offerExtractor->extract($doc,$customers,$projects);
  $q=$this->db->getQueryBuilder();$q->select('ps.id','ps.project_id','ps.supplier_id','ps.trade','ps.purchase_no','ps.confirmation_no','ps.confirmation_status','ps.receipt_status','s.name AS supplier_name','p.project_no','p.title AS project_title')->from('re_erp_project_suppliers','ps')->leftJoin('ps','re_erp_suppliers','s',$q->expr()->eq('s.id','ps.supplier_id'))->leftJoin('ps','re_erp_projects','p',$q->expr()->eq('p.id','ps.project_id'))->orderBy('p.project_no','ASC')->addOrderBy('s.name','ASC');$projectSuppliers=$q->executeQuery()->fetchAll();
  $suggestedProject=(int)($doc['project_id']??$doc['suggested_project_id']??0);$suggestedSupplier=(int)($doc['supplier_id']??$doc['suggested_supplier_id']??0);$suggestedNo=$this->normaliseMatchToken((string)($doc['suggested_document_no']??''));$recommendedProjectSupplierId=0;$bestScore=0;$bestCount=0;
  foreach($projectSuppliers as $sp){if($suggestedProject<=0||(int)$sp['project_id']!==$suggestedProject)continue;if($suggestedSupplier>0&&(int)$sp['supplier_id']!==$suggestedSupplier)continue;$score=40;if($suggestedSupplier>0)$score+=30;foreach(['purchase_no','confirmation_no'] as $field){$token=$this->normaliseMatchToken((string)($sp[$field]??''));if($suggestedNo!==''&&$token!==''&&($suggestedNo===$token||str_contains($suggestedNo,$token)||str_contains($token,$suggestedNo)))$score+=50;}if($score>$bestScore){$bestScore=$score;$recommendedProjectSupplierId=(int)$sp['id'];$bestCount=1;}elseif($score===$bestScore){$bestCount++;}}
  if($bestCount>1&&$bestScore<100)$recommendedProjectSupplierId=0;
  return new TemplateResponse($this->appName,'document_detail',['document'=>$doc,'extractedOffer'=>$extracted,'duplicateWarning'=>$this->documents->duplicateWarning((string)($doc['checksum']??''),$id),'customers'=>$customers,'projects'=>$projects,'orders'=>$this->rows('re_erp_orders','order_no'),'suppliers'=>$this->rows('re_erp_suppliers','name'),'projectSuppliers'=>$projectSuppliers,'recommendedProjectSupplierId'=>$recommendedProjectSupplierId,'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error',''),'urlGenerator'=>$this->url]);
 }
 private function normaliseMatchToken(string $value):string{return strtolower((string)preg_replace('/[^a-zA-Z0-9]+/','',$value));}
 private function normaliseOfferPositions(array $descriptions,array $quantities,array $units,array $unitPrices,array $totals):array{$positions=[];foreach($descriptions as $index=>$description){$description=trim((string)$description);if($description==='')continue;$quantity=max(0.01,(float)str_replace(',','.',(string)($quantities[$index]??1)));$unit=trim((string)($units[$index]??'Stk.'))?:'Stk.';$unitPrice=(float)str_replace(',','.',(string)($unitPrices[$index]??0));$total=(float)str_replace(',','.',(string)($totals[$index]??0));if($total<=0)$total=round($quantity*$unitPrice,2);if($unitPrice<=0&&$quantity>0)$unitPrice=round($total/$quantity,2);$positions[]=['description'=>$description,'quantity'=>$quantity,'unit'=>$unit,'unit_price'=>$unitPrice,'total_price'=>$total];}return $positions;}
 private function rows(string $table,string $order):array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->orderBy($order,'ASC');return $q->executeQuery()->fetchAll();}
 private function go(string $message):RedirectResponse{return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.index',['message'=>$message]));}
 private function goError(string $message):RedirectResponse{return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.index',['error'=>$message]));}
}
