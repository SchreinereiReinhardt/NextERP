<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;
use OCP\IDBConnection;
final class ProjectPhaseService {
 private const DEFAULTS=[
  ['Anfrage',10,0,0],['Angebot',20,0,0],['Auftrag',30,1,0],['Fertigung',40,1,0],
  ['Montage',50,1,0],['Abnahme',60,1,0],['Abrechnung',70,1,0],['Abgeschlossen',80,1,1],
 ];
 public function __construct(private IDBConnection $db){}
 public function all(bool $activeOnly=false):array{$this->ensureDefaults();$q=$this->db->getQueryBuilder();$q->select('*')->from('re_erp_project_phases');if($activeOnly)$q->where($q->expr()->eq('active',$q->createNamedParameter(1)));$q->orderBy('sort_order','ASC')->addOrderBy('id','ASC');return $q->executeQuery()->fetchAll();}
 public function names(bool $activeOnly=true):array{return array_values(array_map(static fn(array $r):string=>(string)$r['name'],$this->all($activeOnly)));}
 public function findByName(string $name):?array{foreach($this->all(false) as $r)if(strcasecmp((string)$r['name'],trim($name))===0)return $r;return null;}
 public function save(?int $id,string $name,int $sortOrder,bool $active,bool $freeze,bool $closes):void{$this->ensureDefaults();$name=trim($name);if($name==='')throw new \InvalidArgumentException('Bezeichnung der Projektphase fehlt.');foreach($this->all(false) as $r)if((int)$r['id']!==($id??0)&&strcasecmp((string)$r['name'],$name)===0)throw new \InvalidArgumentException('Diese Projektphase existiert bereits.');$q=$this->db->getQueryBuilder();$data=['name'=>$name,'sort_order'=>$sortOrder,'active'=>$active?1:0,'freeze_calculation'=>$freeze?1:0,'closes_project'=>$closes?1:0];if($id&&$id>0){$q->update('re_erp_project_phases');foreach($data as $k=>$v)$q->set($k,$q->createNamedParameter($v));$q->where($q->expr()->eq('id',$q->createNamedParameter($id)))->executeStatement();}else{$q->insert('re_erp_project_phases');$data['created_at']=date('Y-m-d H:i:s');$vals=[];foreach($data as $k=>$v)$vals[$k]=$q->createNamedParameter($v);$q->values($vals)->executeStatement();}}
 private function ensureDefaults():void{$q=$this->db->getQueryBuilder();$q->select('id')->from('re_erp_project_phases')->setMaxResults(1);if($q->executeQuery()->fetch()!==false)return;$now=date('Y-m-d H:i:s');foreach(self::DEFAULTS as [$name,$sort,$freeze,$close]){$q=$this->db->getQueryBuilder();$q->insert('re_erp_project_phases')->values(['name'=>$q->createNamedParameter($name),'sort_order'=>$q->createNamedParameter($sort),'active'=>$q->createNamedParameter(1),'freeze_calculation'=>$q->createNamedParameter($freeze),'closes_project'=>$q->createNamedParameter($close),'created_at'=>$q->createNamedParameter($now)])->executeStatement();}}
}
