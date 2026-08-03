<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version005005Date20260803090000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_workday_materials')){
   $t=$s->createTable('re_erp_workday_materials');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('workday_entry_id','bigint',['notnull'=>false]);
   $t->addColumn('timer_id','bigint',['notnull'=>false]);
   $t->addColumn('material_id','bigint',['notnull'=>false]);
   $t->addColumn('description','string',['length'=>1024,'notnull'=>true]);
   $t->addColumn('quantity','decimal',['precision'=>12,'scale'=>3,'default'=>0,'notnull'=>true]);
   $t->addColumn('unit','string',['length'=>32,'notnull'=>false]);
   $t->addColumn('unit_price','decimal',['precision'=>12,'scale'=>2,'default'=>0,'notnull'=>true]);
   $t->addColumn('total_price','decimal',['precision'=>14,'scale'=>2,'default'=>0,'notnull'=>true]);
   $t->addColumn('imported_to_report_id','bigint',['notnull'=>false]);
   $t->addColumn('invoice_id','bigint',['notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addIndex(['workday_entry_id'],'re_erp_wdm_entry');
   $t->addIndex(['timer_id'],'re_erp_wdm_timer');
   $t->addIndex(['material_id'],'re_erp_wdm_material');
   $t->addIndex(['imported_to_report_id'],'re_erp_wdm_report');
  }
  if($s->hasTable('re_erp_report_items')){
   $t=$s->getTable('re_erp_report_items');
   if(!$t->hasColumn('unit_price'))$t->addColumn('unit_price','decimal',['precision'=>12,'scale'=>2,'default'=>0,'notnull'=>true]);
   if(!$t->hasColumn('total_price'))$t->addColumn('total_price','decimal',['precision'=>14,'scale'=>2,'default'=>0,'notnull'=>true]);
   if(!$t->hasColumn('source_workday_material_id'))$t->addColumn('source_workday_material_id','bigint',['notnull'=>false]);
   if(!$t->hasIndex('re_erp_ri_source_material'))$t->addIndex(['source_workday_material_id'],'re_erp_ri_source_material');
  }
  return $s;
 }
}
