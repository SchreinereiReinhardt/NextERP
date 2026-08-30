<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;
use OCP\IConfig;

final class NumberService {
 private const APP='reinhardterp';
 private const DEFAULTS=[
  'customer'=>['label'=>'Kunden','prefix'=>'','yearly'=>false,'separator'=>'','start'=>10000,'width'=>5],
  'project'=>['label'=>'Projekte','prefix'=>'P','yearly'=>true,'separator'=>'-','start'=>0,'width'=>4],
  'report'=>['label'=>'Rapporte','prefix'=>'R','yearly'=>true,'separator'=>'-','start'=>0,'width'=>4],
  'material'=>['label'=>'Material','prefix'=>'M','yearly'=>false,'separator'=>'','start'=>0,'width'=>5],
  'invoice'=>['label'=>'Rechnungen','prefix'=>'RE','yearly'=>true,'separator'=>'-','start'=>0,'width'=>4],
  'offer'=>['label'=>'Angebote','prefix'=>'AN','yearly'=>true,'separator'=>'-','start'=>0,'width'=>4],
  'order'=>['label'=>'Aufträge','prefix'=>'AU','yearly'=>true,'separator'=>'-','start'=>0,'width'=>4],
 ];
 public function __construct(private IDBConnection $db,private IConfig $config) {}
 public function next(string $type): string {
  $d=$this->definition($type);$key=$this->sequenceKey($type,$d);
  $this->db->beginTransaction();
  try {
   $qb=$this->db->getQueryBuilder();$qb->select('current_value')->from('re_erp_sequences')->where($qb->expr()->eq('sequence_key',$qb->createNamedParameter($key)));
   $current=$qb->executeQuery()->fetchOne();
   if($current===false){$current=$d['start']+1;$ins=$this->db->getQueryBuilder();$ins->insert('re_erp_sequences')->values(['sequence_key'=>$ins->createNamedParameter($key),'current_value'=>$ins->createNamedParameter($current),'updated_at'=>$ins->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();}
   else{$current=(int)$current+1;$up=$this->db->getQueryBuilder();$up->update('re_erp_sequences')->set('current_value',$up->createNamedParameter($current))->set('updated_at',$up->createNamedParameter(date('Y-m-d H:i:s')))->where($up->expr()->eq('sequence_key',$up->createNamedParameter($key)))->executeStatement();}
   $this->db->commit();
  }catch(\Throwable $e){$this->db->rollBack();throw $e;}
  return $this->format($d,(int)$current);
 }
 public function settings():array{
  $out=[];foreach(array_keys(self::DEFAULTS) as $type){$d=$this->definition($type);$next=$this->current($this->sequenceKey($type,$d));$next=$next===null?$d['start']+1:$next+1;$out[$type]=$d+['type'=>$type,'next'=>$next,'preview'=>$this->format($d,$next)];}return $out;
 }
 public function saveSettings(array $input):void{
  foreach(self::DEFAULTS as $type=>$default){
   $prefix=trim((string)($input[$type]['prefix']??$default['prefix']));if(strlen($prefix)>12)throw new \InvalidArgumentException('Präfix bei '.$default['label'].' ist zu lang.');
   $yearly=!empty($input[$type]['yearly']);$separator=(string)($input[$type]['separator']??$default['separator']);if(!in_array($separator,['','-','/','.'],true))$separator='-';
   $width=max(1,min(10,(int)($input[$type]['width']??$default['width'])));$next=max(1,(int)($input[$type]['next']??($default['start']+1)));
   $this->config->setAppValue(self::APP,'number_'.$type.'_prefix',$prefix);$this->config->setAppValue(self::APP,'number_'.$type.'_yearly',$yearly?'1':'0');$this->config->setAppValue(self::APP,'number_'.$type.'_separator',$separator);$this->config->setAppValue(self::APP,'number_'.$type.'_width',(string)$width);
   $d=$this->definition($type);$key=$this->sequenceKey($type,$d);$this->setCurrent($key,$next-1);
  }
 }
 private function definition(string $type):array{
  if(!isset(self::DEFAULTS[$type]))throw new \InvalidArgumentException('Unbekannter Nummernkreis.');$d=self::DEFAULTS[$type];
  $d['prefix']=$this->config->getAppValue(self::APP,'number_'.$type.'_prefix',(string)$d['prefix']);$d['yearly']=$this->config->getAppValue(self::APP,'number_'.$type.'_yearly',$d['yearly']?'1':'0')==='1';$d['separator']=$this->config->getAppValue(self::APP,'number_'.$type.'_separator',(string)$d['separator']);$d['width']=max(1,min(10,(int)$this->config->getAppValue(self::APP,'number_'.$type.'_width',(string)$d['width'])));return $d;
 }
 private function sequenceKey(string $type,array $d):string{return $type.($d['yearly']?'-'.date('Y'):'');}
 private function format(array $d,int $number):string{$serial=str_pad((string)$number,(int)$d['width'],'0',STR_PAD_LEFT);$body=(string)$d['prefix'].$serial;return $d['yearly']?date('Y').(string)$d['separator'].$body:$body;}
 private function current(string $key):?int{$q=$this->db->getQueryBuilder();$q->select('current_value')->from('re_erp_sequences')->where($q->expr()->eq('sequence_key',$q->createNamedParameter($key)));$v=$q->executeQuery()->fetchOne();return $v===false?null:(int)$v;}
 private function setCurrent(string $key,int $value):void{$q=$this->db->getQueryBuilder();$q->select('sequence_key')->from('re_erp_sequences')->where($q->expr()->eq('sequence_key',$q->createNamedParameter($key)));$exists=$q->executeQuery()->fetchOne()!==false;if($exists){$u=$this->db->getQueryBuilder();$u->update('re_erp_sequences')->set('current_value',$u->createNamedParameter($value))->set('updated_at',$u->createNamedParameter(date('Y-m-d H:i:s')))->where($u->expr()->eq('sequence_key',$u->createNamedParameter($key)))->executeStatement();}else{$i=$this->db->getQueryBuilder();$i->insert('re_erp_sequences')->values(['sequence_key'=>$i->createNamedParameter($key),'current_value'=>$i->createNamedParameter($value),'updated_at'=>$i->createNamedParameter(date('Y-m-d H:i:s'))])->executeStatement();}}
}
