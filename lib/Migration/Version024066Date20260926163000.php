<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024066Date20260926163000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output,Closure $schemaClosure,array $options):?ISchemaWrapper{
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_delivery_notes')){
   $t=$s->createTable('re_erp_delivery_notes');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('delivery_no','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('order_id','bigint',['notnull'=>true]);
   $t->addColumn('offer_id','bigint',['notnull'=>false]);
   $t->addColumn('customer_id','bigint',['notnull'=>true]);
   $t->addColumn('project_id','bigint',['notnull'=>false]);
   $t->addColumn('delivery_date','date',['notnull'=>true]);
   $t->addColumn('status','string',['length'=>24,'default'=>'open','notnull'=>true]);
   $t->addColumn('notes','text',['notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_deliv_pk');
   $t->addUniqueIndex(['delivery_no'],'re_erp_deliv_no');
   $t->addIndex(['order_id'],'re_erp_deliv_order');
  }
  if(!$s->hasTable('re_erp_delivery_items')){
   $t=$s->createTable('re_erp_delivery_items');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('delivery_id','bigint',['notnull'=>true]);
   $t->addColumn('position_no','integer',['notnull'=>true]);
   $t->addColumn('description','text',['notnull'=>true]);
   $t->addColumn('quantity','decimal',['precision'=>14,'scale'=>3,'notnull'=>true]);
   $t->addColumn('unit','string',['length'=>32,'notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_deliv_item_pk');
   $t->addIndex(['delivery_id'],'re_erp_deliv_item');
  }
  return $s;
 }
}
