<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024064Date20260926150000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options):?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_billing_checks')){
   $t=$schema->createTable('re_erp_billing_checks');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('project_id','bigint',['notnull'=>true]);
   $t->addColumn('source_type','string',['length'=>24,'notnull'=>true]);
   $t->addColumn('source_id','bigint',['notnull'=>true]);
   $t->addColumn('status','string',['length'=>24,'notnull'=>true]);
   $t->addColumn('invoice_id','bigint',['notnull'=>false]);
   $t->addColumn('note','string',['length'=>255,'notnull'=>false]);
   $t->addColumn('updated_by','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_bill_check_pk');
   $t->addUniqueIndex(['source_type','source_id'],'re_erp_bill_check_src');
   $t->addIndex(['project_id','status'],'re_erp_bill_check_proj');
  }
  return $schema;
 }
}
