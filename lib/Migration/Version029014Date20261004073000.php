<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version029014Date20261004073000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_stock_movements')){
   $t=$schema->getTable('re_erp_stock_movements');
   if(!$t->hasColumn('project_supplier_id'))$t->addColumn('project_supplier_id','bigint',['notnull'=>false]);
   if(!$t->hasIndex('re_erp_smov_ps'))$t->addIndex(['project_supplier_id'],'re_erp_smov_ps');
  }
  return $schema;
 }
}
