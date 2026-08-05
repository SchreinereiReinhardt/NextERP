<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;
final class PdfService {
 private function text(?string $value):string{$value=$value??'';$converted=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$value);return $converted===false?$value:$converted;}
 public function createReport(array $report,?array $project,?array $customer,array $hours,array $items,?array $logo=null,array $photos=[]):string{
  require_once __DIR__.'/../tfpdf/tfpdf.php';
  $pdf=new \tFPDF('P','mm','A4');$pdf->SetMargins(15,15,15);$pdf->SetAutoPageBreak(true,18);$pdf->AddPage();
  if($logo&&isset($logo['content'],$logo['extension'])){$tmp=tempnam(sys_get_temp_dir(),'erp-logo-');if($tmp!==false){file_put_contents($tmp,$logo['content']);try{$type=strtoupper($logo['extension']==='jpg'?'JPG':$logo['extension']);$pdf->Image($tmp,150,14,45,0,$type);}catch(\Throwable){}@unlink($tmp);}}
  $pdf->SetTextColor(25,25,25);$pdf->SetFont('Helvetica','B',18);$pdf->Cell(125,10,$this->text('Rapport '.($report['report_no']??'')),0,1);$pdf->SetFont('Helvetica','',10);$pdf->Cell(0,6,$this->text('Schreinerei Reinhardt'),0,1);$pdf->Ln(2);$pdf->SetDrawColor(90,90,90);$pdf->SetLineWidth(.5);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());$pdf->Ln(6);
  foreach([['Kunde:',trim(($customer['customer_no']??'').' '.($customer['name']??''))],['Projekt:',trim(($project['project_no']??'').' '.($project['title']??''))]] as [$label,$value]){$pdf->SetFont('Helvetica','B',10);$pdf->Cell(32,6,$this->text($label),0,0);$pdf->SetFont('Helvetica','',10);$pdf->Cell(0,6,$this->text($value),0,1);}
  $pdf->SetFont('Helvetica','B',10);$pdf->Cell(32,6,$this->text('Datum:'),0,0);$pdf->SetFont('Helvetica','',10);$pdf->Cell(55,6,$this->text((string)($report['report_date']??'')),0,0);$pdf->SetFont('Helvetica','B',10);$pdf->Cell(24,6,$this->text('Status:'),0,0);$pdf->SetFont('Helvetica','',10);$pdf->Cell(0,6,$this->text((string)($report['status']??'')),0,1);
  $pdf->Ln(5);$pdf->SetFont('Helvetica','B',14);$pdf->MultiCell(0,7,$this->text((string)($report['title']??'')));$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text('Ausgeführte Tätigkeiten'),0,1);$pdf->SetFont('Helvetica','',10);$pdf->MultiCell(0,5,$this->text((string)($report['description']??'')));
  if(!empty($report['customer_note'])){$pdf->Ln(3);$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text('Hinweis für den Auftraggeber'),0,1);$pdf->SetFont('Helvetica','',10);$pdf->MultiCell(0,5,$this->text((string)$report['customer_note']));}
  $pdf->Ln(4);$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text('Arbeitszeiten'),0,1);$pdf->SetFillColor(235,235,235);$pdf->SetFont('Helvetica','B',9);$pdf->Cell(30,7,$this->text('Datum'),1,0,'L',true);$pdf->Cell(45,7,$this->text('Mitarbeiter'),1,0,'L',true);$pdf->Cell(20,7,$this->text('Std.'),1,0,'L',true);$pdf->Cell(85,7,$this->text('Tätigkeit'),1,1,'L',true);$pdf->SetFont('Helvetica','',9);
  foreach($hours as $hour){$activity=$this->text((string)($hour['activity']??''));$lines=max(1,(int)ceil(strlen($activity)/48));$height=max(7,$lines*4.5);if($pdf->GetY()+$height>275)$pdf->AddPage();$y=$pdf->GetY();$pdf->Cell(30,$height,$this->text((string)($hour['work_date']??'')),1,0);$pdf->Cell(45,$height,$this->text((string)($hour['user_id']??'')),1,0);$pdf->Cell(20,$height,$this->text(number_format((float)($hour['hours']??0),2,',','.')),1,0);$pdf->MultiCell(85,4.5,$activity,1);if($pdf->GetY()<$y+$height)$pdf->SetY($y+$height);}
  $pdf->Ln(4);$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text('Material'),0,1);$pdf->SetFillColor(235,235,235);$pdf->SetFont('Helvetica','B',9);$pdf->Cell(105,7,$this->text('Beschreibung'),1,0,'L',true);$pdf->Cell(25,7,$this->text('Menge'),1,0,'L',true);$pdf->Cell(25,7,$this->text('Einheit'),1,0,'L',true);$pdf->Cell(25,7,$this->text('Bemerkung'),1,1,'L',true);$pdf->SetFont('Helvetica','',9);foreach($items as $item){$pdf->Cell(105,7,$this->text((string)($item['description']??'')),1,0);$pdf->Cell(25,7,$this->text(number_format((float)($item['quantity']??0),3,',','.')),1,0);$pdf->Cell(25,7,$this->text((string)($item['unit']??'')),1,0);$pdf->Cell(25,7,$this->text((string)($item['notes']??'')),1,1);}
  $this->addPhotoDocumentation($pdf,$photos);
  $pdf->Ln(8);$pdf->SetFont('Helvetica','B',10);$pdf->Cell(0,6,$this->text('Unterschrift Auftraggeber'),0,1);$signatureData=(string)($report['signature_data']??'');if(str_starts_with($signatureData,'data:image/png;base64,')){$raw=base64_decode(substr($signatureData,strpos($signatureData,',')+1),true);if($raw!==false){$tmp=tempnam(sys_get_temp_dir(),'erp-sign-');if($tmp!==false){file_put_contents($tmp,$raw);try{$pdf->Image($tmp,$pdf->GetX(),$pdf->GetY(),75,20,'PNG');}catch(\Throwable){}@unlink($tmp);$pdf->Ln(23);}}}$pdf->SetFont('Helvetica','',10);$signed=trim((string)($report['signed_by']??''));$signedAt=trim((string)($report['signed_at']??''));$pdf->Cell(0,6,$this->text($signed!==''?$signed.($signedAt!==''?' · '.$signedAt:''):'Noch nicht unterschrieben'),0,1);
  $pdf->Ln(5);$pdf->SetFont('Helvetica','B',10);$pdf->Cell(0,6,$this->text('Unterschrift Monteur'),0,1);$technicianData=(string)($report['technician_signature_data']??'');if(str_starts_with($technicianData,'data:image/png;base64,')){$raw=base64_decode(substr($technicianData,strpos($technicianData,',')+1),true);if($raw!==false){$tmp=tempnam(sys_get_temp_dir(),'erp-tech-sign-');if($tmp!==false){file_put_contents($tmp,$raw);try{$pdf->Image($tmp,$pdf->GetX(),$pdf->GetY(),75,20,'PNG');}catch(\Throwable){}@unlink($tmp);$pdf->Ln(23);}}}$pdf->SetFont('Helvetica','',10);$technician=trim((string)($report['technician_signed_by']??''));$technicianAt=trim((string)($report['technician_signed_at']??''));$pdf->Cell(0,6,$this->text($technician!==''?$technician.($technicianAt!==''?' · '.$technicianAt:''):'Noch nicht unterschrieben'),0,1);return $pdf->Output('S');
 }

 private function addPhotoDocumentation(\tFPDF $pdf,array $photos):void{
  $prepared=[];
  foreach($photos as $photo){
   if(!is_array($photo))continue;
   $content=$photo['content']??null;$mime=strtolower((string)($photo['mime']??''));
   if(!is_string($content)||$content===''||!str_starts_with($mime,'image/'))continue;
   $image=@getimagesizefromstring($content);if($image===false)continue;
   $type=match($image[2]??0){IMAGETYPE_JPEG=>'JPG',IMAGETYPE_PNG=>'PNG',default=>null};
   if($type===null)continue;
   $category=trim((string)($photo['category']??''));if($category==='')$category=$this->photoCategory((string)($photo['path']??''));
   $prepared[]=['content'=>$content,'type'=>$type,'width'=>(int)$image[0],'height'=>(int)$image[1],'category'=>$category,'name'=>(string)($photo['name']??basename((string)($photo['path']??'Foto'))),'createdAt'=>(string)($photo['created_at']??'')];
  }
  if($prepared===[])return;
  $order=['Vorher'=>0,'Montage'=>1,'Nachher'=>2,'Schaden'=>3,'Abnahme'=>4,'Sonstige'=>5];
  usort($prepared,static fn(array $a,array $b):int=>[$order[$a['category']]??99,$a['createdAt'],$a['name']]<=>[$order[$b['category']]??99,$b['createdAt'],$b['name']]);
  $pdf->AddPage();$pdf->SetFont('Helvetica','B',14);$pdf->Cell(0,8,$this->text('Fotodokumentation'),0,1);$pdf->SetFont('Helvetica','',9);$pdf->Cell(0,6,$this->text(count($prepared).' Foto'.(count($prepared)===1?'':'s')),0,1);$pdf->Ln(3);
  $current='';
  foreach($prepared as $photo){
   if($photo['category']!==$current){$current=$photo['category'];if($pdf->GetY()>245)$pdf->AddPage();$pdf->Ln(3);$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text($current),0,1);}
   $this->addPhoto($pdf,$photo);
  }
 }
 private function addPhoto(\tFPDF $pdf,array $photo):void{
  $maxWidth=170.0;$maxHeight=105.0;$captionHeight=12.0;
  $ratio=$photo['width']>0&&$photo['height']>0?$photo['width']/$photo['height']:1.0;
  $width=$maxWidth;$height=$width/max(.01,$ratio);
  if($height>$maxHeight){$height=$maxHeight;$width=$height*$ratio;}
  if($pdf->GetY()+$height+$captionHeight>275)$pdf->AddPage();
  $x=15+($maxWidth-$width)/2;$y=$pdf->GetY();$tmp=tempnam(sys_get_temp_dir(),'erp-photo-');
  if($tmp===false)return;
  try{file_put_contents($tmp,$photo['content']);$pdf->Image($tmp,$x,$y,$width,$height,$photo['type']);$pdf->SetY($y+$height+2);$pdf->SetFont('Helvetica','',8);$caption=$photo['name'];if($photo['createdAt']!=='')$caption.=' · '.$photo['createdAt'];$pdf->MultiCell(0,4,$this->text($caption),0,'C');$pdf->Ln(4);}catch(\Throwable){}finally{@unlink($tmp);}
 }
 private function photoCategory(string $path):string{
  foreach(['Vorher','Nachher','Montage','Schaden','Abnahme','Sonstige'] as $category)if(str_contains('/'.$path.'/','/'.$category.'/'))return $category;
  return 'Sonstige';
 }

}
