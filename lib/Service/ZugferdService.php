<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

/** ZUGFeRD 2.5.2 / Factur-X 1.09.2 structured invoice data, EN16931 profile (CII). */
final class ZugferdService {
 private const GUIDELINE='urn:cen.eu:en16931:2017';
 /** Build a real hybrid ZUGFeRD/Factur-X PDF/A-3 from Betrio's visible PDF and CII XML. */
 public function createPdf(array $invoice,array $customer,array $items,array $company,string $sourcePdf):string{
  $xml=$this->createXml($invoice,$customer,$items,$company);
  $autoload=dirname(__DIR__,2).'/vendor/autoload.php';
  if(!is_file($autoload))throw new \RuntimeException('ZUGFeRD-Bibliothek fehlt (vendor/autoload.php).');
  require_once $autoload;
  if(!class_exists(\horstoeko\zugferd\ZugferdDocumentPdfMerger::class))throw new \RuntimeException('ZUGFeRD-PDF-Komponente ist nicht verfügbar.');
  try{
   $sourcePdf=$this->preparePdfForPdfA($sourcePdf);
   $merger=new \horstoeko\zugferd\ZugferdDocumentPdfMerger($xml,$sourcePdf);
   $merger->setAdditionalCreatorTool('Betrio');
   $merger->setPdfAConformanceLevel(\horstoeko\zugferd\ZugferdDocumentPdfBuilderAbstract::PDFA_CONFORMANCE_LEVEL_BASIC);
   $merger->setAttachmentRelationshipTypeToAlternative();
   $merger->generateDocument();
   $pdf=$merger->downloadString();
   if($pdf===''||!str_starts_with($pdf,'%PDF-'))throw new \RuntimeException('Die ZUGFeRD-PDF-Erzeugung lieferte keine gültige PDF-Datei.');
   return $pdf;
  }catch(\Throwable $e){throw new \RuntimeException('ZUGFeRD-PDF konnte nicht erzeugt werden: '.$e->getMessage(),0,$e);}
 }
 public function createXml(array $invoice,array $customer,array $items,array $company):string{
  $this->validate($invoice,$customer,$items,$company);
  $d=new \DOMDocument('1.0','UTF-8');$d->formatOutput=true;
  $r=$d->createElementNS('urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100','rsm:CrossIndustryInvoice');
  $r->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:ram','urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100');
  $r->setAttributeNS('http://www.w3.org/2000/xmlns/','xmlns:udt','urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100');
  $d->appendChild($r);
  $ctx=$this->e($d,$r,'rsm:ExchangedDocumentContext');$g=$this->e($d,$ctx,'ram:GuidelineSpecifiedDocumentContextParameter');$this->t($d,$g,'ram:ID',self::GUIDELINE);
  $doc=$this->e($d,$r,'rsm:ExchangedDocument');$this->t($d,$doc,'ram:ID',(string)$invoice['invoice_no']);$this->t($d,$doc,'ram:TypeCode',(string)(($invoice['invoice_type']??'')==='credit'?'381':'380'));$dt=$this->e($d,$doc,'ram:IssueDateTime');$ds=$this->t($d,$dt,'udt:DateTimeString',date('Ymd',strtotime((string)$invoice['invoice_date'])));$ds->setAttribute('format','102');
  $tr=$this->e($d,$r,'rsm:SupplyChainTradeTransaction');
  $taxMode=(string)($invoice['tax_mode']??'standard19');$rate=(float)($invoice['vat_rate']??19);$cat=$taxMode==='reverse_charge_13b'?'AE':($taxMode==='small_business'?'E':($rate>0?'S':'Z'));if(in_array($cat,['AE','E'],true))$rate=0.0;
  foreach($items as $i){if(!empty($i['is_alternative']))continue;$li=$this->e($d,$tr,'ram:IncludedSupplyChainTradeLineItem');$ld=$this->e($d,$li,'ram:AssociatedDocumentLineDocument');$this->t($d,$ld,'ram:LineID',(string)($i['id']??''));$prod=$this->e($d,$li,'ram:SpecifiedTradeProduct');$this->t($d,$prod,'ram:Name',$this->plain((string)$i['description']));$agr=$this->e($d,$li,'ram:SpecifiedLineTradeAgreement');$np=$this->e($d,$agr,'ram:NetPriceProductTradePrice');$this->t($d,$np,'ram:ChargeAmount',$this->money((float)$i['unit_price']));$del=$this->e($d,$li,'ram:SpecifiedLineTradeDelivery');$q=$this->t($d,$del,'ram:BilledQuantity',$this->decimal((float)$i['quantity']));$q->setAttribute('unitCode',$this->unitCode((string)($i['unit']??'')));$set=$this->e($d,$li,'ram:SpecifiedLineTradeSettlement');$lt=$this->e($d,$set,'ram:ApplicableTradeTax');$this->t($d,$lt,'ram:TypeCode','VAT');$this->t($d,$lt,'ram:CategoryCode',$cat);$this->t($d,$lt,'ram:RateApplicablePercent',$this->decimal($rate));$sum=$this->e($d,$set,'ram:SpecifiedTradeSettlementLineMonetarySummation');$this->t($d,$sum,'ram:LineTotalAmount',$this->money((float)$i['total_price']));}
  $agr=$this->e($d,$tr,'ram:ApplicableHeaderTradeAgreement');$seller=$this->e($d,$agr,'ram:SellerTradeParty');$this->t($d,$seller,'ram:Name',(string)$company['name']);$this->partyAddress($d,$seller,$company);$sellerVat=trim((string)($company['vatId']??''));$sellerTaxNo=trim((string)($company['taxNo']??''));if($sellerVat!==''){$reg=$this->e($d,$seller,'ram:SpecifiedTaxRegistration');$id=$this->t($d,$reg,'ram:ID',$sellerVat);$id->setAttribute('schemeID','VA');}if($sellerTaxNo!==''){$reg=$this->e($d,$seller,'ram:SpecifiedTaxRegistration');$id=$this->t($d,$reg,'ram:ID',$sellerTaxNo);$id->setAttribute('schemeID','FC');}$buyer=$this->e($d,$agr,'ram:BuyerTradeParty');$this->t($d,$buyer,'ram:Name',CustomerAddressService::partyName($customer));$this->partyAddress($d,$buyer,$customer);$buyerVat=trim((string)($customer['vat_id']??$customer['vatId']??''));if($buyerVat!==''){$reg=$this->e($d,$buyer,'ram:SpecifiedTaxRegistration');$id=$this->t($d,$reg,'ram:ID',$buyerVat);$id->setAttribute('schemeID','VA');}
  $del=$this->e($d,$tr,'ram:ApplicableHeaderTradeDelivery');if(!empty($invoice['service_date'])){$occ=$this->e($d,$del,'ram:ActualDeliverySupplyChainEvent');$date=$this->e($d,$occ,'ram:OccurrenceDateTime');$x=$this->t($d,$date,'udt:DateTimeString',date('Ymd',strtotime((string)$invoice['service_date'])));$x->setAttribute('format','102');}
  $currency=$this->currency($invoice);$set=$this->e($d,$tr,'ram:ApplicableHeaderTradeSettlement');$this->t($d,$set,'ram:InvoiceCurrencyCode',$currency);$iban=preg_replace('/\s+/','',(string)($company['bank1_iban']??''));if($iban!==''){$pm=$this->e($d,$set,'ram:SpecifiedTradeSettlementPaymentMeans');$this->t($d,$pm,'ram:TypeCode','58');$acc=$this->e($d,$pm,'ram:PayeePartyCreditorFinancialAccount');$this->t($d,$acc,'ram:IBANID',$iban);}
  $tax=$this->e($d,$set,'ram:ApplicableTradeTax');$taxAmount=round((float)$invoice['gross_amount']-(float)$invoice['net_amount'],2);$this->t($d,$tax,'ram:CalculatedAmount',$this->money($taxAmount));$this->t($d,$tax,'ram:TypeCode','VAT');if($cat==='AE')$this->t($d,$tax,'ram:ExemptionReason','Steuerschuldnerschaft des Leistungsempfängers');elseif($cat==='E')$this->t($d,$tax,'ram:ExemptionReason','Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG');$this->t($d,$tax,'ram:BasisAmount',$this->money((float)$invoice['net_amount']));$this->t($d,$tax,'ram:CategoryCode',$cat);$this->t($d,$tax,'ram:RateApplicablePercent',$this->decimal($rate));
  if(!empty($invoice['due_date'])){$pt=$this->e($d,$set,'ram:SpecifiedTradePaymentTerms');$dd=$this->e($d,$pt,'ram:DueDateDateTime');$x=$this->t($d,$dd,'udt:DateTimeString',date('Ymd',strtotime((string)$invoice['due_date'])));$x->setAttribute('format','102');}
  $sum=$this->e($d,$set,'ram:SpecifiedTradeSettlementHeaderMonetarySummation');$net=(float)$invoice['net_amount'];$gross=(float)$invoice['gross_amount'];$this->t($d,$sum,'ram:LineTotalAmount',$this->money($net));$this->t($d,$sum,'ram:TaxBasisTotalAmount',$this->money($net));$ta=$this->t($d,$sum,'ram:TaxTotalAmount',$this->money($taxAmount));$ta->setAttribute('currencyID',$currency);$this->t($d,$sum,'ram:GrandTotalAmount',$this->money($gross));$this->t($d,$sum,'ram:DuePayableAmount',$this->money($gross));
  return $d->saveXML()?:'';
 }
 public function warnings(array $invoice,array $customer,array $items,array $company):array{$e=[];if(($invoice['status']??'')==='draft')$e[]='Die Rechnung muss zuerst finalisiert werden.';foreach(['invoice_no'=>'Rechnungsnummer','invoice_date'=>'Rechnungsdatum'] as $k=>$l)if(trim((string)($invoice[$k]??''))==='')$e[]=$l.' fehlt.';foreach(['name'=>'Firmenname','street'=>'Straße','zip'=>'PLZ','city'=>'Ort'] as $k=>$l)if(trim((string)($company[$k]??''))==='')$e[]='Eigene Firmendaten: '.$l.' fehlt.';if(trim((string)($customer['name']??''))==='')$e[]='Kunde: Name fehlt.';if(!$items)$e[]='Rechnungspositionen fehlen.';return $e;}
 public function validate(array $i,array $c,array $it,array $co):void{$e=$this->warnings($i,$c,$it,$co);$taxMode=(string)($i['tax_mode']??'standard19');$rate=(float)($i['vat_rate']??19);if($taxMode!=='small_business'&&($rate>0||$taxMode==='reverse_charge_13b')&&trim((string)($co['vatId']??''))===''&&trim((string)($co['taxNo']??''))==='')$e[]='Eigene Firmendaten: USt-IdNr. oder Steuernummer fehlt.';if($taxMode==='reverse_charge_13b'&&trim((string)($c['vat_id']??$c['vatId']??''))==='')$e[]='Kunde: Für §13b / Reverse Charge fehlt die USt-IdNr.';if($e)throw new \InvalidArgumentException('ZUGFeRD kann nicht erzeugt werden: '.implode(' ',$e));}

 private function preparePdfForPdfA(string $sourcePdf):string{
  if(!str_starts_with($sourcePdf,'%PDF-'))return $sourcePdf;
  $gs=$this->findGhostscript();
  if($gs===null)return $sourcePdf;
  $in=tempnam(sys_get_temp_dir(),'betrio-zf-in-');$out=tempnam(sys_get_temp_dir(),'betrio-zf-out-');
  if($in===false||$out===false)return $sourcePdf;
  @unlink($out);file_put_contents($in,$sourcePdf);
  $cmd=escapeshellarg($gs).' -q -dNOPAUSE -dBATCH -dSAFER -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dEmbedAllFonts=true -dSubsetFonts=true -dCompressFonts=true -dPDFSETTINGS=/prepress -sOutputFile='.escapeshellarg($out).' '.escapeshellarg($in).' 2>&1';
  $lines=[];$code=1;exec($cmd,$lines,$code);@unlink($in);
  if($code!==0||!is_file($out)){@unlink($out);return $sourcePdf;}
  $pdf=(string)file_get_contents($out);@unlink($out);
  return str_starts_with($pdf,'%PDF-')?$pdf:$sourcePdf;
 }
 private function findGhostscript():?string{foreach(['/usr/bin/ghostscript','/usr/bin/gs'] as $bin)if(is_executable($bin))return $bin;return null;}
 private function partyAddress(\DOMDocument $d,\DOMElement $p,array $x):void{$a=$this->e($d,$p,'ram:PostalTradeAddress');$zip=(string)($x['postal_code']??$x['zip']??'');$this->opt($d,$a,'ram:PostcodeCode',$zip);$this->opt($d,$a,'ram:LineOne',(string)($x['street']??''));$this->opt($d,$a,'ram:LineTwo',(string)($x['address_addition']??''));$this->opt($d,$a,'ram:CityName',(string)($x['city']??''));$this->opt($d,$a,'ram:CountryID',$this->country((string)($x['country']??'DE')));}
 private function e(\DOMDocument $d,\DOMElement $p,string $n):\DOMElement{$e=$d->createElement($n);$p->appendChild($e);return $e;} private function t(\DOMDocument $d,\DOMElement $p,string $n,string $v):\DOMElement{$e=$d->createElement($n);$e->appendChild($d->createTextNode($v));$p->appendChild($e);return $e;} private function opt(\DOMDocument $d,\DOMElement $p,string $n,string $v):void{if(trim($v)!=='')$this->t($d,$p,$n,$v);} private function currency(array $invoice):string{$currency=strtoupper(trim((string)($invoice['currency']??'EUR')));return preg_match('/^[A-Z]{3}$/',$currency)?$currency:'EUR';}
 private function money(float $v):string{return number_format($v,2,'.','');}private function decimal(float $v):string{$s=rtrim(rtrim(number_format($v,4,'.',''),'0'),'.');return $s===''?'0':$s;}private function plain(string $v):string{return trim(html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_HTML5,'UTF-8'));}private function country(string $v):string{$v=mb_strtolower(trim($v));return match($v){'','de','deutschland','germany'=>'DE','at','österreich','austria'=>'AT','ch','schweiz','switzerland'=>'CH',default=>strtoupper(substr($v,0,2))};}private function unitCode(string $u):string{$u=mb_strtolower(trim($u));return match($u){'std','h','stunde','stunden'=>'HUR','m','meter'=>'MTR','m²','m2','qm'=>'MTK','m³','m3'=>'MTQ','kg'=>'KGM','l','liter'=>'LTR',default=>'C62'};}
}
