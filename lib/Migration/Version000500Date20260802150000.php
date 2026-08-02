<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000500Date20260802150000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_reports')){$t=$schema->getTable('re_erp_reports');
   if(!$t->hasColumn('customer_id'))$t->addColumn('customer_id','bigint',['notnull'=>false]);
   if(!$t->hasColumn('folder_path'))$t->addColumn('folder_path','string',['length'=>1024,'notnull'=>false]);
   if(!$t->hasColumn('signature_path'))$t->addColumn('signature_path','string',['length'=>1024,'notnull'=>false]);
   if(!$t->hasColumn('print_path'))$t->addColumn('print_path','string',['length'=>1024,'notnull'=>false]);
   if(!$t->hasColumn('finalized_at'))$t->addColumn('finalized_at','datetime',['notnull'=>false]);
   $t->addIndex(['customer_id'],'re_erp_report_customer');
  }
  return $schema;
 }
 public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {}
}
