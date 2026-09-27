<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024090Date20260927130000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_stock_movements')){$t=$schema->getTable('re_erp_stock_movements');
   if(!$t->hasColumn('unit_cost'))$t->addColumn('unit_cost','decimal',['precision'=>12,'scale'=>4,'notnull'=>false]);
  }
  return $schema;
 }
}
