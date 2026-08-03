<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IUserSession;
final class FolderService {
 public function __construct(private IRootFolder $rootFolder,private IUserSession $userSession){}
 public function ensureCustomerFolder(string $customerNo,string $name):string{$root=$this->userRoot();$base=$this->folder($root,'ERP');$customers=$this->folder($base,'Kunden');$folderName=$this->safe(($customerNo!==''?$customerNo.'_':'').$name);$customer=$this->folder($customers,$folderName);foreach(['01_Kundendaten','02_Anfragen','03_Angebote','04_Auftraege','05_Projekte','06_Rechnungen','07_Korrespondenz','08_Bilder','09_Sonstiges'] as $sub)$this->folder($customer,$sub);return 'ERP/Kunden/'.$folderName;}
 public function ensureProjectFolder(string $customerPath,string $projectNo,string $title):string{$node=$this->folderByPath($customerPath);$projects=$this->folder($node,'05_Projekte');$folderName=$this->safe($projectNo.'_'.$title);$project=$this->folder($projects,$folderName);foreach(['01_Aufmass','02_Planung','03_Zeichnungen','04_Material','05_Bestellungen','06_Rapporte','07_Fotos','08_Abnahme','09_Rechnung'] as $sub)$this->folder($project,$sub);return trim($customerPath,'/').'/05_Projekte/'.$folderName;}
 public function ensureReportRoot(string $projectPath):string{$project=$this->folderByPath($projectPath);$this->folder($project,'06_Rapporte');return trim($projectPath,'/').'/06_Rapporte';}
 public function ensureBillingBatchFolder(string $customerPath,string $batchNo):string{$customer=$this->folderByPath($customerPath);$invoices=$this->folder($customer,'06_Rechnungen');$prep=$this->folder($invoices,'Abrechnungsvorbereitung');$name=$this->safe($batchNo);$this->folder($prep,$name);return trim($customerPath,'/').'/06_Rechnungen/Abrechnungsvorbereitung/'.$name;}
 public function ensureInvoiceFolder(string $customerPath,string $invoiceNo):string{$customer=$this->folderByPath($customerPath);$invoices=$this->folder($customer,'06_Rechnungen');$name=$this->safe($invoiceNo);$this->folder($invoices,$name);return trim($customerPath,'/').'/06_Rechnungen/'.$name;}
 public function ensureReportFolder(string $projectPath,string $reportNo,string $title):string{$project=$this->folderByPath($projectPath);$reports=$this->folder($project,'06_Rapporte');$name=$this->safe($reportNo.'_'.$title);$folder=$this->folder($reports,$name);$this->folder($folder,'Anhaenge');return trim($projectPath,'/').'/06_Rapporte/'.$name;}
 public function write(string $folderPath,string $name,string $content):string{$folder=$this->folderByPath($folderPath);$safe=$this->safeFile($name);try{$node=$folder->get($safe);if($node instanceof File){$node->putContent($content);}else{throw new \RuntimeException('Dateiname ist bereits ein Ordner.');}}catch(\OCP\Files\NotFoundException){$folder->newFile($safe,$content);}return trim($folderPath,'/').'/'.$safe;}
 public function listFiles(string $folderPath,int $limit=20,int $maxDepth=2):array{
  $folder=$this->folderByPath($folderPath);$rows=[];$this->collectFiles($folder,trim($folderPath,'/'),0,max(0,$maxDepth),$rows);
  usort($rows,static fn(array $a,array $b):int=>$b['mtime']<=>$a['mtime']);
  return array_slice($rows,0,max(1,$limit));
 }
 public function ensureFolderPath(string $basePath,string $relative=''):string{
  $path=trim($basePath,'/');$node=$this->folderByPath($path);
  foreach(explode('/',trim($relative,'/')) as $part){if($part!==''){$part=$this->safe($part);$node=$this->folder($node,$part);$path.='/'.$part;}}
  return $path;
 }
 public function writeFromLocalFile(string $folderPath,string $name,string $tmpPath):string{$content=file_get_contents($tmpPath);if($content===false)throw new \RuntimeException('Datei konnte nicht gelesen werden.');return $this->write($folderPath,$name,$content);}
 public function exists(string $path):bool{try{$this->userRoot()->get(trim($path,'/'));return true;}catch(\Throwable){return false;}}
 public function fileInfo(string $path):array{$node=$this->userRoot()->get(trim($path,'/'));if(!$node instanceof File)throw new \RuntimeException('Datei nicht gefunden.');return ['id'=>$node->getId(),'name'=>$node->getName(),'path'=>trim($path,'/'),'mime'=>$node->getMimeType(),'size'=>$node->getSize(),'mtime'=>$node->getMTime(),'checksum'=>$this->checksum($node)];}
 public function moveFile(string $sourcePath,string $targetFolder,string $newName):string{$source=$this->userRoot()->get(trim($sourcePath,'/'));if(!$source instanceof File)throw new \RuntimeException('Quelldatei nicht gefunden.');$target=$this->folderByPath($targetFolder);$safe=$this->safeFile($newName);$source->move(rtrim($target->getPath(),'/').'/'.$safe);return trim($targetFolder,'/').'/'.$safe;}
 public function readFile(string $path):array{$node=$this->userRoot()->get(trim($path,'/'));if(!$node instanceof File)throw new \RuntimeException('Datei nicht gefunden.');return ['content'=>$node->getContent(),'mime'=>$node->getMimeType(),'name'=>$node->getName(),'size'=>$node->getSize(),'id'=>$node->getId()];}
 public function saveCompanyLogo(string $content,string $extension):string{$root=$this->userRoot();$erp=$this->folder($root,'ERP');$settings=$this->folder($erp,'Einstellungen');foreach(['Firmenlogo.png','Firmenlogo.jpg','Firmenlogo.jpeg'] as $old){try{$node=$settings->get($old);if($node instanceof File)$node->delete();}catch(\OCP\Files\NotFoundException){}}$ext=in_array(strtolower($extension),['png','jpg','jpeg'],true)?strtolower($extension):'png';return $this->write('ERP/Einstellungen','Firmenlogo.'.$ext,$content);}
 public function companyLogo():?array{foreach(['png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg'] as $ext=>$mime){try{$node=$this->folderByPath('ERP/Einstellungen')->get('Firmenlogo.'.$ext);if($node instanceof File)return ['content'=>$node->getContent(),'mime'=>$mime,'extension'=>$ext,'path'=>'ERP/Einstellungen/Firmenlogo.'.$ext];}catch(\Throwable){}}return null;}
 private function collectFiles(Folder $folder,string $path,int $depth,int $maxDepth,array &$rows):void{
  foreach($folder->getDirectoryListing() as $node){
   $nodePath=$path.'/'.$node->getName();
   if($node instanceof File){$rows[]=['id'=>$node->getId(),'name'=>$node->getName(),'path'=>$nodePath,'mime'=>$node->getMimeType(),'size'=>$node->getSize(),'mtime'=>$node->getMTime(),'checksum'=>$this->checksum($node),'isFolder'=>false];}
   elseif($node instanceof Folder&&$depth<$maxDepth){$this->collectFiles($node,$nodePath,$depth+1,$maxDepth,$rows);}
  }
 }
 private function checksum(File $file):?string{try{return hash('sha256',$file->getContent());}catch(\Throwable){return null;}}
 private function folderByPath(string $path):Folder{$node=$this->userRoot();foreach(explode('/',trim($path,'/')) as $part){if($part!=='')$node=$this->folder($node,$part);}if(!$node instanceof Folder)throw new \RuntimeException('Pfad ist kein Ordner.');return $node;}
 private function userRoot():Folder{$uid=$this->userSession->getUser()?->getUID();if($uid===null)throw new \RuntimeException('Kein angemeldeter Benutzer.');return $this->rootFolder->getUserFolder($uid);}
 private function folder($parent,string $name):Folder{try{$node=$parent->get($name);if(!$node instanceof Folder)throw new \RuntimeException('Ordnername ist bereits als Datei vorhanden: '.$name);return $node;}catch(\OCP\Files\NotFoundException){return $parent->newFolder($name);}}
 private function safe(string $value):string{$value=preg_replace('/[^\pL\pN._-]+/u','_',trim($value))??'Ordner';return trim($value,'._-')?:'Ordner';}
 private function safeFile(string $value):string{$value=preg_replace('/[^\pL\pN._ -]+/u','_',trim($value))??'Datei';return trim($value,'. ')?:'Datei';}
}
