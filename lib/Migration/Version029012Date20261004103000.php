<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version029012Date20261004103000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_purchase_items')){
   $t=$schema->createTable('re_erp_purchase_items');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('project_supplier_id','bigint',['notnull'=>true]);
   $t->addColumn('material_id','bigint',['notnull'=>true]);
   $t->addColumn('ordered_qty','decimal',['precision'=>12,'scale'=>3,'notnull'=>true]);
   $t->addColumn('received_qty','decimal',['precision'=>12,'scale'=>3,'default'=>0,'notnull'=>true]);
   $t->addColumn('unit_cost','decimal',['precision'=>12,'scale'=>4,'notnull'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_pitem_pk');
   $t->addIndex(['project_supplier_id'],'re_erp_pitem_ps');
   $t->addIndex(['material_id'],'re_erp_pitem_mat');
  }
  return $schema;
 }
}
