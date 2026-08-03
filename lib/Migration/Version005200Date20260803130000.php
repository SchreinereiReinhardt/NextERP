<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version005200Date20260803130000 extends SimpleMigrationStep {
 public function __construct(private IDBConnection $db) {}
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_material_groups')){$t=$s->createTable('re_erp_material_groups');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('name','string',['length'=>160,'notnull'=>true]);$t->addColumn('description','string',['length'=>255,'notnull'=>false]);$t->addColumn('active','smallint',['default'=>1,'notnull'=>true]);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id']);$t->addUniqueIndex(['name'],'re_erp_mat_group_name');}
  if(!$s->hasTable('re_erp_suppliers')){$t=$s->createTable('re_erp_suppliers');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('name','string',['length'=>200,'notnull'=>true]);$t->addColumn('contact_person','string',['length'=>160,'notnull'=>false]);$t->addColumn('email','string',['length'=>255,'notnull'=>false]);$t->addColumn('phone','string',['length'=>64,'notnull'=>false]);$t->addColumn('customer_no','string',['length'=>80,'notnull'=>false]);$t->addColumn('notes','text',['notnull'=>false]);$t->addColumn('active','smallint',['default'=>1,'notnull'=>true]);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id']);$t->addUniqueIndex(['name'],'re_erp_supplier_name');}
  if($s->hasTable('re_erp_materials')){$t=$s->getTable('re_erp_materials');if(!$t->hasColumn('material_group_id'))$t->addColumn('material_group_id','bigint',['notnull'=>false]);if(!$t->hasColumn('supplier_id'))$t->addColumn('supplier_id','bigint',['notnull'=>false]);if(!$t->hasColumn('storage_location'))$t->addColumn('storage_location','string',['length'=>120,'notnull'=>false]);if(!$t->hasColumn('barcode'))$t->addColumn('barcode','string',['length'=>120,'notnull'=>false]);if(!$t->hasColumn('reorder_quantity'))$t->addColumn('reorder_quantity','decimal',['precision'=>12,'scale'=>3,'default'=>0,'notnull'=>true]);if(!$t->hasIndex('re_erp_material_group_idx'))$t->addIndex(['material_group_id'],'re_erp_material_group_idx');if(!$t->hasIndex('re_erp_material_supplier_idx'))$t->addIndex(['supplier_id'],'re_erp_material_supplier_idx');if(!$t->hasIndex('re_erp_material_barcode_idx'))$t->addIndex(['barcode'],'re_erp_material_barcode_idx');}
  return $s;
 }
 public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
  $groups=[];$suppliers=[];
  $q=$this->db->getQueryBuilder();$rows=$q->select('id','material_group','supplier')->from('re_erp_materials')->executeQuery()->fetchAllAssociative();
  foreach($rows as $row){$gid=null;$sid=null;$group=trim((string)($row['material_group']??''));$supplier=trim((string)($row['supplier']??''));
   if($group!==''){if(!isset($groups[$group])){$f=$this->db->getQueryBuilder();$f->select('id')->from('re_erp_material_groups')->where($f->expr()->eq('name',$f->createNamedParameter($group)));$groups[$group]=(int)($f->executeQuery()->fetchOne()?:0);if($groups[$group]===0){$i=$this->db->getQueryBuilder();$i->insert('re_erp_material_groups')->values(['name'=>$i->createNamedParameter($group),'description'=>$i->createNamedParameter(null),'active'=>$i->createNamedParameter(1),'created_at'=>$i->createNamedParameter(date('Y-m-d H:i:s'))]);$i->executeStatement();$f=$this->db->getQueryBuilder();$f->select('id')->from('re_erp_material_groups')->where($f->expr()->eq('name',$f->createNamedParameter($group)));$groups[$group]=(int)$f->executeQuery()->fetchOne();}}$gid=$groups[$group];}
   if($supplier!==''){if(!isset($suppliers[$supplier])){$f=$this->db->getQueryBuilder();$f->select('id')->from('re_erp_suppliers')->where($f->expr()->eq('name',$f->createNamedParameter($supplier)));$suppliers[$supplier]=(int)($f->executeQuery()->fetchOne()?:0);if($suppliers[$supplier]===0){$i=$this->db->getQueryBuilder();$i->insert('re_erp_suppliers')->values(['name'=>$i->createNamedParameter($supplier),'contact_person'=>$i->createNamedParameter(null),'email'=>$i->createNamedParameter(null),'phone'=>$i->createNamedParameter(null),'customer_no'=>$i->createNamedParameter(null),'notes'=>$i->createNamedParameter(null),'active'=>$i->createNamedParameter(1),'created_at'=>$i->createNamedParameter(date('Y-m-d H:i:s'))]);$i->executeStatement();$f=$this->db->getQueryBuilder();$f->select('id')->from('re_erp_suppliers')->where($f->expr()->eq('name',$f->createNamedParameter($supplier)));$suppliers[$supplier]=(int)$f->executeQuery()->fetchOne();}}$sid=$suppliers[$supplier];}
   $u=$this->db->getQueryBuilder();$u->update('re_erp_materials')->set('material_group_id',$u->createNamedParameter($gid))->set('supplier_id',$u->createNamedParameter($sid))->where($u->expr()->eq('id',$u->createNamedParameter((int)$row['id'])))->executeStatement();
  }
 }
}
