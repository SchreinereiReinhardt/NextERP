<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024052Date20260926133000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options):?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_project_suppliers')){
   $t=$schema->createTable('re_erp_project_suppliers');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('project_id','bigint',['notnull'=>true]);
   $t->addColumn('supplier_id','bigint',['notnull'=>true]);
   $t->addColumn('trade','string',['length'=>160,'notnull'=>false]);
   $t->addColumn('purchase_no','string',['length'=>100,'notnull'=>false]);
   $t->addColumn('confirmation_status','string',['length'=>20,'default'=>'open','notnull'=>true]);
   $t->addColumn('confirmation_no','string',['length'=>100,'notnull'=>false]);
   $t->addColumn('expected_week','integer',['notnull'=>false]);
   $t->addColumn('receipt_status','string',['length'=>20,'default'=>'open','notnull'=>true]);
   $t->addColumn('mounting_relevant','smallint',['default'=>1,'notnull'=>true]);
   $t->addColumn('confirmation_document_id','bigint',['notnull'=>false]);
   $t->addColumn('delivery_document_id','bigint',['notnull'=>false]);
   $t->addColumn('notes','text',['notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_proj_sup_pk');
   $t->addIndex(['project_id','supplier_id'],'re_erp_proj_sup_project');
   $t->addIndex(['project_id','receipt_status'],'re_erp_proj_sup_receipt');
  }
  return $schema;
 }
}
