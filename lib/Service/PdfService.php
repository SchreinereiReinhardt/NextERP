<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

final class PdfService {
 private function text(?string $value):string{$value=$value??'';$converted=iconv('UTF-8','Windows-1252//TRANSLIT//IGNORE',$value);return $converted===false?$value:$converted;}
 private function line(\tFPDF $pdf,int $r=210,int $g=215,int $b=220):void{$pdf->SetDrawColor($r,$g,$b);$pdf->SetLineWidth(.25);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());}
 private function section(\tFPDF $pdf,string $title):void{$pdf->Ln(5);$pdf->SetTextColor(30,38,46);$pdf->SetFont('Helvetica','B',11);$pdf->Cell(0,7,$this->text($title),0,1);$this->line($pdf);$pdf->Ln(3);}
 private function companyLine(array $company):string{return trim(implode(' · ',array_filter([(string)($company['street']??''),trim((string)($company['zip']??'').' '.(string)($company['city']??'')),(string)($company['phone']??''),(string)($company['email']??'')])));}
 public function createReport(array $report,?array $project,?array $customer,array $hours,array $items,?array $logo=null,array $photos=[],array $company=[]):string{
  require_once __DIR__.'/../tfpdf/tfpdf.php';
  $pdf=new \tFPDF('P','mm','A4');$pdf->SetMargins(15,14,15);$pdf->SetAutoPageBreak(true,18);$pdf->AddPage();
  $companyName=trim((string)($company['name']??'')) ?: 'NextERP';

  // Centered logo with sufficient top breathing room.
  if($logo&&isset($logo['content'],$logo['extension'])){
   $tmp=tempnam(sys_get_temp_dir(),'erp-logo-');
   if($tmp!==false){
    file_put_contents($tmp,$logo['content']);
    try{
     $type=strtoupper($logo['extension']==='jpg'?'JPG':$logo['extension']);
     $pdf->Image($tmp,65,20,80,0,$type);
    }catch(\Throwable){}
    @unlink($tmp);
   }
  }else{
   $pdf->SetXY(15,22);$pdf->SetTextColor(28,37,46);$pdf->SetFont('Helvetica','B',16);$pdf->Cell(180,8,$this->text($companyName),0,1,'C');
  }

  // Readable company block left, report identification right.
  $headerY=48;
  $pdf->SetXY(15,$headerY);
  $pdf->SetTextColor(45,53,61);$pdf->SetFont('Helvetica','B',9.5);
  $pdf->Cell(92,5,$this->text($companyName),0,1);
  $pdf->SetFont('Helvetica','',8.5);$pdf->SetTextColor(88,97,106);
  foreach([
   trim((string)($company['street']??'')),
   trim((string)($company['zip']??'').' '.(string)($company['city']??'')),
   trim((string)($company['country']??'')),
   trim(((string)($company['phone']??''))!==''?'Tel. '.(string)$company['phone']:''),
   trim((string)($company['email']??'')),
   trim((string)($company['website']??''))
  ] as $companyRow){
   if($companyRow==='')continue;
   $pdf->SetX(15);$pdf->Cell(92,4.3,$this->text($companyRow),0,1);
  }

  $pdf->SetXY(115,$headerY);$pdf->SetTextColor(105,115,125);$pdf->SetFont('Helvetica','B',8);$pdf->Cell(80,5,$this->text('LEISTUNGSNACHWEIS'),0,1,'R');
  $pdf->SetX(115);$pdf->SetTextColor(28,37,46);$pdf->SetFont('Helvetica','B',22);$pdf->Cell(80,9,$this->text('RAPPORT'),0,1,'R');
  $pdf->SetX(115);$pdf->SetTextColor(18,101,216);$pdf->SetFont('Helvetica','B',10);$pdf->Cell(80,5,$this->text((string)($report['report_no']??'')),0,1,'R');

  $pdf->SetY(max($pdf->GetY(),76));$pdf->SetDrawColor(18,101,216);$pdf->SetLineWidth(.8);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());$pdf->Ln(6);

  $left=15;$top=$pdf->GetY();$cardW=87;$gap=6;
  $pdf->SetFillColor(246,248,250);$pdf->Rect($left,$top,$cardW,18,'F');$pdf->Rect($left+$cardW+$gap,$top,$cardW,18,'F');
  $pdf->SetXY($left+4,$top+3);$pdf->SetTextColor(115,124,133);$pdf->SetFont('Helvetica','B',7);$pdf->Cell($cardW-8,4,$this->text('KUNDE'),0,1);
  $pdf->SetX($left+4);$pdf->SetTextColor(30,38,46);$pdf->SetFont('Helvetica','B',9);$pdf->MultiCell($cardW-8,4,$this->text(trim((string)($customer['customer_no']??'').' '.(string)($customer['name']??''))));
  $pdf->SetXY($left+$cardW+$gap+4,$top+3);$pdf->SetTextColor(115,124,133);$pdf->SetFont('Helvetica','B',7);$pdf->Cell($cardW-8,4,$this->text('PROJEKT / DATUM'),0,1);
  $pdf->SetX($left+$cardW+$gap+4);$pdf->SetTextColor(30,38,46);$pdf->SetFont('Helvetica','B',9);$pdf->MultiCell($cardW-8,4,$this->text(trim((string)($project['project_no']??'').' '.(string)($project['title']??'')).' · '.(string)($report['report_date']??'')));
  $pdf->SetY($top+22);

  $this->section($pdf,(string)($report['title']??'Ausgeführte Arbeiten'));
  $pdf->SetTextColor(40,47,54);$pdf->SetFont('Helvetica','',9.5);$pdf->MultiCell(0,5,$this->text((string)($report['description']??'')));
  if(!empty($report['customer_note'])){$pdf->Ln(2);$pdf->SetFillColor(250,247,238);$pdf->SetFont('Helvetica','B',8.5);$pdf->MultiCell(0,5,$this->text('Hinweis für den Auftraggeber: '.(string)$report['customer_note']),0,'L',true);}

  $this->section($pdf,'Arbeitszeiten');
  $pdf->SetFillColor(243,245,247);$pdf->SetTextColor(85,95,105);$pdf->SetFont('Helvetica','B',7.5);
  foreach([['Datum',25],['Mitarbeiter',42],['Std.',18],['Tätigkeit',95]] as [$h,$w])$pdf->Cell($w,6,$this->text($h),0,0,'L',true);$pdf->Ln();
  $total=0.0;$pdf->SetTextColor(35,42,49);$pdf->SetFont('Helvetica','',8.5);
  foreach($hours as $hour){$total+=(float)($hour['hours']??0);$activity=$this->text((string)($hour['activity']??''));$height=max(6,ceil(max(1,strlen($activity))/58)*4.2);if($pdf->GetY()+$height>274)$pdf->AddPage();$y=$pdf->GetY();$pdf->Cell(25,$height,$this->text((string)($hour['work_date']??'')),0,0);$pdf->Cell(42,$height,$this->text((string)($hour['display_name']??$hour['user_id']??'')),0,0);$pdf->Cell(18,$height,$this->text(number_format((float)($hour['hours']??0),2,',','.')),0,0);$pdf->MultiCell(95,4.2,$activity,0);if($pdf->GetY()<$y+$height)$pdf->SetY($y+$height);$this->line($pdf,232,235,238);}
  $pdf->SetFont('Helvetica','B',8.5);$pdf->Cell(67,6,$this->text('Gesamt'),0,0,'R');$pdf->Cell(18,6,$this->text(number_format($total,2,',','.')),0,1);

  $this->section($pdf,'Material');
  $pdf->SetFillColor(243,245,247);$pdf->SetTextColor(85,95,105);$pdf->SetFont('Helvetica','B',7.5);
  foreach([['Beschreibung',100],['Menge',24],['Einheit',22],['Bemerkung',34]] as [$h,$w])$pdf->Cell($w,6,$this->text($h),0,0,'L',true);$pdf->Ln();
  $pdf->SetTextColor(35,42,49);$pdf->SetFont('Helvetica','',8.5);
  foreach($items as $item){if($pdf->GetY()+7>274)$pdf->AddPage();$pdf->Cell(100,7,$this->text((string)($item['description']??'')),0,0);$pdf->Cell(24,7,$this->text(number_format((float)($item['quantity']??0),3,',','.')),0,0);$pdf->Cell(22,7,$this->text((string)($item['unit']??'')),0,0);$pdf->Cell(34,7,$this->text((string)($item['notes']??'')),0,1);$this->line($pdf,232,235,238);}

  $this->addPhotoDocumentation($pdf,$photos);
  $this->section($pdf,'Abnahme / Unterschrift');
  $signatureData=(string)($report['signature_data']??'');if(str_starts_with($signatureData,'data:image/png;base64,')){$raw=base64_decode(substr($signatureData,strpos($signatureData,',')+1),true);if($raw!==false){$tmp=tempnam(sys_get_temp_dir(),'erp-sign-');if($tmp!==false){file_put_contents($tmp,$raw);try{$pdf->Image($tmp,15,$pdf->GetY(),65,18,'PNG');}catch(\Throwable){}@unlink($tmp);$pdf->Ln(20);}}}
  $pdf->SetFont('Helvetica','',8);$pdf->SetTextColor(90,98,106);$signed=trim((string)($report['signed_by']??''));$signedAt=(string)($report['signed_at']??'');$pdf->Cell(85,5,$this->text($signed!==''?$signed:'Name Auftraggeber'),0,0);$pdf->Cell(95,5,$this->text($signedAt!==''?$signedAt:'Datum / Unterschrift'),0,1,'R');
  $pdf->Ln(3);$this->line($pdf);$pdf->Ln(3);$footer=array_filter([(string)($company['name']??''),(string)($company['website']??''),(string)($company['email']??'')]);$pdf->SetFont('Helvetica','',7);$pdf->SetTextColor(125,132,140);$pdf->Cell(0,4,$this->text(implode(' · ',$footer)),0,1,'C');
  return $pdf->Output('S');
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
