<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

/** Formats optional structured customer identity fields without changing legacy customer data. */
final class CustomerAddressService {
 public static function partyName(array $customer):string{
  $type=(string)($customer['customer_type']??'business');
  $person=self::personName($customer,false);
  if($type==='private' && $person!=='')return $person;
  return trim((string)($customer['name']??'')) ?: $person;
 }
 public static function personName(array $customer,bool $withSalutation=true):string{
  $parts=[];
  if($withSalutation){$sal=trim((string)($customer['salutation']??''));if($sal!=='')$parts[]=$sal;}
  foreach(['title','first_name','last_name'] as $key){$v=trim((string)($customer[$key]??''));if($v!=='')$parts[]=$v;}
  return trim(implode(' ',$parts));
 }
 public static function recipientLines(array $customer):array{
  $type=(string)($customer['customer_type']??'business');$lines=[];
  $legacyName=trim((string)($customer['name']??''));$person=self::personName($customer,true);
  if($type==='private'){
   $lines[]=$person!==''?$person:$legacyName;
  }else{
   if($legacyName!=='')$lines[]=$legacyName;
   // Structured person data on a company is an optional addressed recipient.
   if($person!=='')$lines[]=$person;
  }
  $addition=trim((string)($customer['address_addition']??''));if($addition!=='')$lines[]=$addition;
  $street=trim((string)($customer['street']??''));if($street!=='')$lines[]=$street;
  $zip=trim((string)($customer['postal_code']??($customer['zip']??'')));$city=trim((string)($customer['city']??''));$place=trim($zip.' '.$city);if($place!=='')$lines[]=$place;
  $country=trim((string)($customer['country']??''));if($country!==''&&!in_array(mb_strtolower($country),['de','d','deutschland','germany'],true))$lines[]=$country;
  return array_values(array_filter($lines,static fn($v)=>trim((string)$v)!==''));
 }
}
