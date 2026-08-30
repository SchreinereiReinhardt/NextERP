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
  $companyName=trim((string)($company['name']??'')) ?: 'Betrio';

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



 public function createCommercialDocument(string $type,array $doc,?array $customer,?array $project,array $items,?array $logo=null,array $company=[]):string{
  require_once __DIR__.'/../tfpdf/tfpdf.php';
  $pdf=new \tFPDF('P','mm','A4');$pdf->SetMargins(15,12,15);$pdf->SetAutoPageBreak(true,22);$pdf->AddPage();
  $label=match($type){'offer'=>'Angebot','advance'=>'Abschlagsrechnung','final'=>'Schlussrechnung','credit'=>'Gutschrift',default=>'Rechnung'};
  $number=$type==='offer'?(string)($doc['offer_no']??''):(string)(($doc['status']??'')==='draft'?'ENTWURF':($doc['invoice_no']??''));
  $date=(string)($type==='offer'?($doc['offer_date']??date('Y-m-d')):($doc['invoice_date']??date('Y-m-d')));
  $this->commercialHeader($pdf,$company,$logo);

  $sender=trim(implode(' - ',array_filter([(string)($company['name']??''),(string)($company['street']??''),trim((string)($company['zip']??'').' '.(string)($company['city']??''))])));
  $pdf->SetY(58);$pdf->SetTextColor(90,90,90);$pdf->SetFont('Helvetica','',6.8);$pdf->Cell(103,4,$this->text($sender),0,0);
  $pdf->SetTextColor(30,30,30);$pdf->SetFont('Helvetica','B',8.5);$pdf->Cell(27,4,$this->text('Sachbearbeiter/-in:'),0,0);$pdf->SetFont('Helvetica','',8.5);$pdf->Cell(50,4,$this->text((string)($doc['clerk_name']??$company['owner']??'')),0,1);
  $pdf->SetX(118);$pdf->SetFont('Helvetica','B',8.5);$pdf->Cell(27,4,$this->text('Datum:'),0,0);$pdf->SetFont('Helvetica','',8.5);$pdf->Cell(50,4,$this->text($this->deDate($date)),0,1);
  $pdf->SetX(118);$pdf->SetFont('Helvetica','B',8.5);$pdf->Cell(27,4,$this->text($label.'-Nr.:'),0,0);$pdf->SetFont('Helvetica','',8.5);$pdf->Cell(50,4,$this->text($number),0,1);

  $pdf->SetXY(15,67);$pdf->SetFont('Helvetica','',9.5);$pdf->SetTextColor(20,20,20);
  $name=(string)($customer['name']??'');if($name!=='')$pdf->Cell(95,5,$this->text($name),0,1);
  $address=$this->plain((string)($customer['address']??''));
  if($address!==''){$pdf->SetX(15);$pdf->MultiCell(90,4.7,$this->text($address));}
  else{
   foreach([(string)($customer['street']??''),trim((string)($customer['zip']??'').' '.(string)($customer['city']??''))] as $row)if(trim($row)!==''){$pdf->SetX(15);$pdf->Cell(90,4.7,$this->text($row),0,1);}
  }

  $pdf->SetY(max(96,$pdf->GetY()+5));$pdf->SetFont('Helvetica','B',17);$pdf->Cell(0,9,$this->text($label),0,1);
  $subject=trim((string)($doc['subject']??''));
  if($subject!==''){$pdf->SetFont('Helvetica','B',10);$pdf->MultiCell(0,5,$this->text($this->plain($subject)));$pdf->Ln(1);}

  if($type==='offer'){
   $intro=trim((string)($doc['intro_text']??''));if($intro==='')$intro=trim((string)($company['default_offer_intro']??''));if($intro==='')$intro='Sehr geehrte Damen und Herren, wir freuen uns, Ihnen folgendes Angebot unterbreiten zu dürfen:';
   $pdf->SetFont('Helvetica','',9);$pdf->MultiCell(0,5,$this->text($this->plain($intro)));$pdf->Ln(3);
  }elseif(!empty($project)){
   $projectText=trim((string)($project['project_no']??'').' '.(string)($project['title']??''));
   if($projectText!==''){$pdf->SetFont('Helvetica','',8.5);$pdf->Cell(0,5,$this->text('Projekt: '.$projectText),0,1);$pdf->Ln(2);}
  }

  if($type!=='offer'){
   $intro=trim((string)($doc['intro_text']??''));
   if($intro!==''){$pdf->SetFont('Helvetica','',9);$pdf->MultiCell(0,5,$this->text($this->plain($intro)));$pdf->Ln(3);}
  }
  $this->commercialTableHeader($pdf);
  $net=0.0;
  foreach($items as $idx=>$item){
   $alt=!empty($item['is_alternative']);$line=(float)($item['total_price']??0);if(!$alt)$net+=$line;
   $desc=$this->plain((string)($item['description']??''));
   if($alt)$desc="Alternativposition\n".$desc;
   $lines=max(1,substr_count(wordwrap($desc,52,"\n",true),"\n")+1);$h=max(7,min(28,$lines*4.2));
   if($pdf->GetY()+$h>270){$pdf->AddPage();$this->commercialHeader($pdf,$company,$logo,true);$pdf->SetY(48);$this->commercialTableHeader($pdf);}
   $y=$pdf->GetY();$pdf->SetFont('Helvetica','',8);
   $pdf->Cell(9,$h,$this->text((string)($idx+1)),0,0,'L');
   $pdf->Cell(17,$h,$this->text(number_format((float)($item['quantity']??0),2,',','.')),0,0,'R');
   $pdf->Cell(16,$h,$this->text((string)($item['unit']??'')),0,0,'L');
   $pdf->SetXY(57,$y);$pdf->MultiCell(74,4.2,$this->text($desc),0,'L');
   $pdf->SetXY(131,$y);$pdf->Cell(28,$h,$this->text(number_format((float)($item['unit_price']??0),2,',','.').' EUR'),0,0,'R');
   $pdf->Cell(36,$h,$this->text(number_format($line,2,',','.').' EUR'),0,1,'R');
   if($pdf->GetY()<$y+$h)$pdf->SetY($y+$h);
   $pdf->SetDrawColor(225,225,225);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());
   $this->commercialItemImage($pdf,$item,$company,$logo);
  }

  $storedNet=(float)($doc['net_amount']??$net);$gross=(float)($doc['gross_amount']??$storedNet);$vat=(float)($doc['vat_rate']??19);$tax=$gross-$storedNet;
  if($pdf->GetY()>235)$pdf->AddPage();
  $pdf->Ln(4);$x=122;$w1=42;$w2=31;
  $pdf->SetFont('Helvetica','',9);$pdf->SetX($x);$pdf->Cell($w1,5,$this->text('Summe'),0,0);$pdf->Cell($w2,5,$this->text(number_format($storedNet,2,',','.').' EUR'),0,1,'R');
  $taxMode=(string)($doc['tax_mode']??'standard19');if($taxMode==='small_business'||$taxMode==='reverse_charge_13b'){$pdf->SetX($x);$pdf->Cell($w1,5,$this->text('Umsatzsteuer'),0,0);$pdf->Cell($w2,5,$this->text('0,00 EUR'),0,1,'R');}else{$pdf->SetX($x);$pdf->Cell($w1,5,$this->text('Mehrwertsteuer '.number_format($vat,0,',','.').'%'),0,0);$pdf->Cell($w2,5,$this->text(number_format($tax,2,',','.').' EUR'),0,1,'R');}
  $pdf->SetFont('Helvetica','B',10.5);$pdf->SetX($x);$pdf->Cell($w1,7,$this->text('Gesamtbetrag'),0,0);$pdf->Cell($w2,7,$this->text(number_format($gross,2,',','.').' EUR'),0,1,'R');

  if($type==='final'&&(float)($doc['advance_gross_amount']??0)>0){
   $advance=(float)$doc['advance_gross_amount'];$remaining=$gross-$advance;$pdf->SetFont('Helvetica','',9);
   $pdf->SetX($x);$pdf->Cell($w1,5,$this->text('abzgl. Abschlaege'),0,0);$pdf->Cell($w2,5,$this->text('- '.number_format($advance,2,',','.').' EUR'),0,1,'R');
   $pdf->SetFont('Helvetica','B',10.5);$pdf->SetX($x);$pdf->Cell($w1,7,$this->text('Zahlbetrag'),0,0);$pdf->Cell($w2,7,$this->text(number_format($remaining,2,',','.').' EUR'),0,1,'R');
  }

  $taxNote=$this->plain((string)($doc['tax_note']??''));if($taxNote!==''){$pdf->Ln(6);$pdf->SetFont('Helvetica','B',8.7);$pdf->MultiCell(0,4.7,$this->text($taxNote));}$notes=$this->plain((string)($doc['notes']??''));if($notes!==''){$pdf->Ln(6);$pdf->SetFont('Helvetica','',8.7);$pdf->MultiCell(0,4.7,$this->text($notes));}
  if($type==='offer'){
   $outro=trim((string)($doc['outro_text']??''));if($outro==='')$outro=trim((string)($company['default_offer_outro']??''));if($outro==='')$outro='Wir hoffen, dass unser Angebot Ihnen zusagt und verbleiben mit freundlichen Gruessen';
   $pdf->Ln(8);$pdf->SetFont('Helvetica','',9);$pdf->MultiCell(0,5,$this->text($this->plain($outro)));$pdf->Ln(4);$pdf->Cell(0,5,$this->text((string)($company['owner']??'')),0,1);
  }
  if($type!=='offer' && ($doc['invoice_type']??'')==='final' && !empty($doc['previous_installments'])){
   $pdf->Ln(5);$pdf->SetFont('Helvetica','B',9);$pdf->Cell(0,5,$this->text('Bereits berechnete Abschlaege'),0,1);
   $sumNet=0.0;$sumVat=0.0;$sumGross=0.0;
   $pdf->SetFont('Helvetica','',8.5);
   foreach($doc['previous_installments'] as $a){
    $sumNet+=(float)($a['net_total']??0);$sumVat+=(float)($a['vat_total']??0);$sumGross+=(float)($a['gross_total']??0);
    $name=(string)($a['invoice_no']??('Entwurf #'.($a['id']??'')));
    $pdf->Cell(110,5,$this->text($name.' · '.($a['invoice_date']??'')),0,0);
    $pdf->Cell(0,5,$this->text('- '.number_format((float)($a['gross_total']??0),2,',','.').' EUR'),0,1,'R');
   }
   $pdf->SetFont('Helvetica','B',9);
   $pdf->Cell(110,6,$this->text('Summe Abschlaege'),0,0);
   $pdf->Cell(0,6,$this->text('- '.number_format($sumGross,2,',','.').' EUR'),0,1,'R');
   $remaining=(float)($doc['gross_total']??0)-$sumGross;
   $pdf->Cell(110,7,$this->text('Verbleibender Rechnungsbetrag'),0,0);
   $pdf->Cell(0,7,$this->text(number_format($remaining,2,',','.').' EUR'),0,1,'R');
  }
  if($type!=='offer'){
   $outro=trim((string)($doc['outro_text']??''));
   if($outro!==''){$pdf->Ln(7);$pdf->SetFont('Helvetica','',8.7);$pdf->MultiCell(0,4.7,$this->text($this->plain($outro)));}
  }
  $this->commercialFooter($pdf,$company);
  return $pdf->Output('S');
 }
 private function commercialItemImage(\tFPDF $pdf,array $item,array $company,?array $logo):void{
  $encoded=(string)($item['image_data']??'');$mime=strtolower((string)($item['image_mime']??''));if($encoded===''||!in_array($mime,['image/jpeg','image/png'],true))return;
  $raw=base64_decode($encoded,true);if($raw===false)return;$info=@getimagesizefromstring($raw);if($info===false)return;
  $type=$mime==='image/png'?'PNG':'JPG';$maxW=72.0;$maxH=45.0;$ratio=((int)$info[0]>0&&(int)$info[1]>0)?(int)$info[0]/(int)$info[1]:1.0;$w=$maxW;$h=$w/max(.01,$ratio);if($h>$maxH){$h=$maxH;$w=$h*$ratio;}
  if($pdf->GetY()+$h+8>258){$pdf->AddPage();$this->commercialHeader($pdf,$company,$logo,true);$pdf->SetY(48);$this->commercialTableHeader($pdf);}
  $tmp=tempnam(sys_get_temp_dir(),'erp-item-');if($tmp===false)return;
  try{file_put_contents($tmp,$raw);$y=$pdf->GetY()+3;$pdf->Image($tmp,57,$y,$w,$h,$type);$pdf->SetY($y+$h+3);$pdf->SetFont('Helvetica','',6.8);$pdf->SetTextColor(105,105,105);$name=trim((string)($item['image_name']??''));if($name!==''){$pdf->SetX(57);$pdf->Cell(74,3.5,$this->text($name),0,1);} $pdf->SetTextColor(25,25,25);$pdf->SetDrawColor(225,225,225);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());}catch(\Throwable){}finally{@unlink($tmp);}
 }

 private function commercialHeader(\tFPDF $pdf,array $company,?array $logo,bool $compact=false):void{
  if($logo&&isset($logo['content'],$logo['extension'])){
   $tmp=tempnam(sys_get_temp_dir(),'erp-commercial-logo-');if($tmp!==false){file_put_contents($tmp,$logo['content']);try{$type=strtoupper(($logo['extension']??'png')==='jpg'?'JPG':($logo['extension']??'PNG'));$pdf->Image($tmp,15,11,$compact?52:70,0,$type);}catch(\Throwable){}@unlink($tmp);}
  }else{$pdf->SetXY(15,12);$pdf->SetFont('Helvetica','B',14);$pdf->Cell(80,7,$this->text((string)($company['name']??'Betrio')),0,1);}
  $pdf->SetXY(118,11);$pdf->SetFont('Helvetica','B',8.5);$pdf->SetTextColor(35,35,35);$pdf->Cell(77,4,$this->text((string)($company['name']??'')),0,1,'L');
  $pdf->SetX(118);$pdf->SetFont('Helvetica','',7.7);
  foreach([(string)($company['street']??''),trim((string)($company['zip']??'').' '.(string)($company['city']??'')),((string)($company['phone']??''))!==''?'Telefon: '.(string)$company['phone']:'',((string)($company['mobile']??''))!==''?'Mobil: '.(string)$company['mobile']:'',((string)($company['email']??''))!==''?'Mail: '.(string)$company['email']:'',((string)($company['website']??''))!==''?'Web: '.(string)$company['website']:''] as $row)if(trim($row)!==''){$pdf->SetX(118);$pdf->Cell(77,3.8,$this->text($row),0,1);}
 }
 private function commercialTableHeader(\tFPDF $pdf):void{
  $pdf->SetFillColor(245,245,245);$pdf->SetFont('Helvetica','B',7.6);$pdf->SetTextColor(25,25,25);
  foreach([['Pos.',9,'L'],['Anzahl',17,'R'],['Einheit',16,'L'],['Bezeichnung',74,'L'],['Einzelpreis',28,'R'],['Gesamtpreis',36,'R']] as [$h,$w,$a])$pdf->Cell($w,6,$this->text($h),0,0,$a,true);
  $pdf->Ln();$pdf->SetDrawColor(60,60,60);$pdf->Line(15,$pdf->GetY(),195,$pdf->GetY());
 }
 private function commercialFooter(\tFPDF $pdf,array $company):void{
  $pdf->SetAutoPageBreak(false);
  $y=264;
  $pdf->SetY($y);
  $pdf->SetDrawColor(160,160,160);
  $pdf->Line(15,$y,195,$y);
  $pdf->SetY($y+2);
  $pdf->SetTextColor(80,80,80);

  $x1=15;$x2=62;$x3=103;$x4=149;
  $w1=45;$w2=39;$w3=44;$w4=46;

  $pdf->SetFont('Helvetica','B',6.6);
  $pdf->SetX($x1);$pdf->Cell($w1,3.5,$this->text((string)($company['name']??'')),0,0);
  $pdf->SetX($x2);$pdf->Cell($w2,3.5,$this->text('Steuerdaten'),0,0);
  $pdf->SetX($x3);$pdf->Cell($w3,3.5,$this->text('Bankverbindung 1'),0,0);
  $pdf->SetX($x4);$pdf->Cell($w4,3.5,$this->text('Bankverbindung 2'),0,1);

  $pdf->SetFont('Helvetica','',6.2);

  $left=[
   (string)($company['street']??''),
   trim((string)($company['zip']??'').' '.(string)($company['city']??'')),
   !empty($company['phone'])?'Tel. '.$company['phone']:'',
   (string)($company['email']??''),
   (string)($company['website']??'')
  ];

  $tax=[
   !empty($company['taxNo'])?'St.-Nr. '.$company['taxNo']:'',
   !empty($company['vatId'])?'USt-IdNr. '.$company['vatId']:'',
   !empty($company['registerCourt'])?'Registergericht '.$company['registerCourt']:'',
   !empty($company['registerNo'])?'Reg.-Nr. '.$company['registerNo']:''
  ];

  $bank1=[
   (string)($company['bank1_name']??''),
   !empty($company['bank1_iban'])?'IBAN '.$company['bank1_iban']:'',
   !empty($company['bank1_bic'])?'BIC '.$company['bank1_bic']:''
  ];

  $bank2=[
   (string)($company['bank2_name']??''),
   !empty($company['bank2_iban'])?'IBAN '.$company['bank2_iban']:'',
   !empty($company['bank2_bic'])?'BIC '.$company['bank2_bic']:''
  ];

  for($i=0;$i<5;$i++){
   $pdf->SetX($x1);$pdf->Cell($w1,3.2,$this->text((string)($left[$i]??'')),0,0);
   $pdf->SetX($x2);$pdf->Cell($w2,3.2,$this->text((string)($tax[$i]??'')),0,0);
   $pdf->SetX($x3);$pdf->Cell($w3,3.2,$this->text((string)($bank1[$i]??'')),0,0);
   $pdf->SetX($x4);$pdf->Cell($w4,3.2,$this->text((string)($bank2[$i]??'')),0,1);
  }
 }
 private function plain(string $html):string{
  $html=preg_replace('#<br\s*/?>#i',"\n",$html);$html=preg_replace('#</(p|li|h2|h3|h4)>#i',"\n",$html);$html=preg_replace('#<li[^>]*>#i','• ',$html);return trim(html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'));
 }
 private function deDate(string $value):string{$ts=strtotime($value);return $ts?date('d.m.Y',$ts):$value;}
}
