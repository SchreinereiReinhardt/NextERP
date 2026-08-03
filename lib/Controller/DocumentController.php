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
 #[NoAdminRequired,NoCSRFRequired] public function index(string $status='all',string $type='all',string $q='',string $processing='all'):TemplateResponse{$this->permissions->assert('documents');$this->documents->ensureStructure();return new TemplateResponse($this->appName,'document_inbox',['documents'=>$this->documents->rows($status,$type,$q,$processing),'counts'=>$this->documents->counts(),'status'=>$status,'type'=>$type,'q'=>$q,'processing'=>$processing,'scanInfo'=>$this->documents->scanInfo(),'rules'=>$this->rules->all(),'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error','')]);}
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
 #[NoAdminRequired] public function assign(int $id,string $documentType,?int $customerId=null,?int $projectId=null,?int $supplierId=null,?string $documentNo=null,?string $documentDate=null,?string $dueDate=null,mixed $netAmount=null,mixed $vatAmount=null,mixed $grossAmount=null,string $currency='EUR',?string $notes=null):RedirectResponse{$this->permissions->assert('documents');try{$this->documents->assign($id,['document_type'=>$documentType,'customer_id'=>$customerId,'project_id'=>$projectId,'supplier_id'=>$supplierId,'document_no'=>$documentNo,'document_date'=>$documentDate,'due_date'=>$dueDate,'net_amount'=>$netAmount,'vat_amount'=>$vatAmount,'gross_amount'=>$grossAmount,'currency'=>$currency,'notes'=>$notes]);if($documentType==='offer'){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'message'=>'Dokument wurde zugeordnet. Du kannst es jetzt als ERP-Angebot übernehmen.']));}return $this->go('Dokument wurde zugeordnet, umbenannt und abgelegt.');}catch(\Throwable $e){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'error'=>$e->getMessage()]));}}
 #[NoAdminRequired] public function createRule(string $name,string $matchValue,?string $documentType=null,?int $customerId=null,?int $projectId=null,?int $supplierId=null,int $priority=100):RedirectResponse{$this->permissions->assert('documents');$this->rules->create(['name'=>$name,'match_value'=>$matchValue,'document_type'=>$documentType,'customer_id'=>$customerId,'project_id'=>$projectId,'supplier_id'=>$supplierId,'priority'=>$priority]);return $this->go('Dokumentenregel wurde angelegt.');}
 #[NoAdminRequired] public function deleteRule(int $id):RedirectResponse{$this->permissions->assert('documents');$this->rules->delete($id);return $this->go('Dokumentenregel wurde gelöscht.');}
 #[NoAdminRequired] public function importOffer(int $id,?int $customerId=null,?int $projectId=null,string $title='',string $description='',?string $offerDate=null,?string $validUntil=null,mixed $netAmount=null,mixed $grossAmount=null,mixed $vatRate=null,?string $notes=null,bool $createProject=false,?string $projectTitle=null,array $positionDescription=[],array $positionQuantity=[],array $positionUnit=[],array $positionUnitPrice=[],array $positionTotal=[]):RedirectResponse{$this->permissions->assert('offers');try{$result=$this->offerImport->importOffer($id,['customer_id'=>$customerId,'project_id'=>$projectId,'title'=>$title,'description'=>$description,'offer_date'=>$offerDate,'valid_until'=>$validUntil,'net_amount'=>$netAmount,'gross_amount'=>$grossAmount,'vat_rate'=>$vatRate,'notes'=>$notes,'create_project'=>$createProject,'project_title'=>$projectTitle,'positions'=>$this->normaliseOfferPositions($positionDescription,$positionQuantity,$positionUnit,$positionUnitPrice,$positionTotal)]);return new RedirectResponse($this->url->linkToRoute('reinhardterp.business.offerDetail',['id'=>$result['offer_id']]));}catch(\Throwable $e){return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.review',['id'=>$id,'error'=>$e->getMessage()]));}}
 private function renderDetail(int $id):TemplateResponse{$this->permissions->assert('documents');$doc=$this->documents->get($id);if(!$doc)return new TemplateResponse($this->appName,'document_detail',['missing'=>true]);$customers=$this->rows('re_erp_customers','name');$projects=$this->rows('re_erp_projects','project_no');$extracted=$this->offerExtractor->extract($doc,$customers,$projects);return new TemplateResponse($this->appName,'document_detail',['document'=>$doc,'extractedOffer'=>$extracted,'duplicateWarning'=>$this->documents->duplicateWarning((string)($doc['checksum']??''),$id),'customers'=>$customers,'projects'=>$projects,'suppliers'=>$this->rows('re_erp_suppliers','name'),'message'=>(string)$this->request->getParam('message',''),'error'=>(string)$this->request->getParam('error','')]);}
 private function normaliseOfferPositions(array $descriptions,array $quantities,array $units,array $unitPrices,array $totals):array{$positions=[];foreach($descriptions as $index=>$description){$description=trim((string)$description);if($description==='')continue;$quantity=max(0.01,(float)str_replace(',','.',(string)($quantities[$index]??1)));$unit=trim((string)($units[$index]??'Stk.'))?:'Stk.';$unitPrice=(float)str_replace(',','.',(string)($unitPrices[$index]??0));$total=(float)str_replace(',','.',(string)($totals[$index]??0));if($total<=0)$total=round($quantity*$unitPrice,2);if($unitPrice<=0&&$quantity>0)$unitPrice=round($total/$quantity,2);$positions[]=['description'=>$description,'quantity'=>$quantity,'unit'=>$unit,'unit_price'=>$unitPrice,'total_price'=>$total];}return $positions;}
 private function rows(string $table,string $order):array{$q=$this->db->getQueryBuilder();$q->select('*')->from($table)->orderBy($order,'ASC');return $q->executeQuery()->fetchAllAssociative();}
 private function go(string $message):RedirectResponse{return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.index',['message'=>$message]));}
 private function goError(string $message):RedirectResponse{return new RedirectResponse($this->url->linkToRoute('reinhardterp.document.index',['error'=>$message]));}
}
