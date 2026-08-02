<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

use OCP\IDBConnection;

final class NumberService {
 public function __construct(private IDBConnection $db) {}
 public function next(string $type): string {
  $defs=[
   'customer'=>['prefix'=>'','yearly'=>false,'start'=>10000,'width'=>5],
   'project'=>['prefix'=>'P','yearly'=>true,'start'=>0,'width'=>4],
   'report'=>['prefix'=>'R','yearly'=>true,'start'=>0,'width'=>4],
   'material'=>['prefix'=>'M','yearly'=>false,'start'=>0,'width'=>5],
   'invoice'=>['prefix'=>'RE','yearly'=>true,'start'=>0,'width'=>4],
  ];
  if(!isset($defs[$type])) throw new \InvalidArgumentException('Unbekannter Nummernkreis.');
  $d=$defs[$type]; $key=$type.($d['yearly']?'-'.date('Y'):'');
  $this->db->beginTransaction();
  try {
   $qb=$this->db->getQueryBuilder();
   $qb->select('current_value')->from('re_erp_sequences')->where($qb->expr()->eq('sequence_key',$qb->createNamedParameter($key)));
   $current=$qb->executeQuery()->fetchOne();
   if($current===false){
    $current=$d['start']+1;
    $ins=$this->db->getQueryBuilder();$ins->insert('re_erp_sequences')->values([
     'sequence_key'=>$ins->createNamedParameter($key),
     'current_value'=>$ins->createNamedParameter($current),
     'updated_at'=>$ins->createNamedParameter(date('Y-m-d H:i:s')),
    ])->executeStatement();
   } else {
    $current=(int)$current+1;
    $up=$this->db->getQueryBuilder();$up->update('re_erp_sequences')->set('current_value',$up->createNamedParameter($current))->set('updated_at',$up->createNamedParameter(date('Y-m-d H:i:s')))->where($up->expr()->eq('sequence_key',$up->createNamedParameter($key)))->executeStatement();
   }
   $this->db->commit();
  } catch(\Throwable $e){$this->db->rollBack();throw $e;}
  $serial=str_pad((string)$current,$d['width'],'0',STR_PAD_LEFT);
  if($d['yearly']) return date('Y').'-'.$d['prefix'].$serial;
  return $d['prefix'].$serial;
 }
}
