<?php
use OCP\Util;
Util::addStyle('reinhardterp','document-print');
$company=$_['company']??[];
$customer=$_['customer']??($_['invoiceCustomer']??[]);
$logo=$_['logoDataUri']??null;
function erpAddr(array $c): string {
 $parts=[];
 if(!empty($c['name']))$parts[]=$c['name'];
 if(!empty($c['address'])) return implode('<br>',array_map('htmlspecialchars',$parts)).'<br>'.nl2br(htmlspecialchars((string)$c['address']));
 if(!empty($c['street']))$parts[]=$c['street'];
 $city=trim((string)($c['zip']??'').' '.(string)($c['city']??''));if($city!=='')$parts[]=$city;
 return implode('<br>',array_map('htmlspecialchars',$parts));
}
function erpCompanyLine(array $c): string {
 return trim((string)($c['name']??'').' - '.(string)($c['street']??'').' - '.trim((string)($c['zip']??'').' '.(string)($c['city']??'')),' -');
}
?>
