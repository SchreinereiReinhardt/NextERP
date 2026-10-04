<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version025000Date20261004080000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_stock_movements')){$t=$schema->getTable('re_erp_stock_movements');
   if(!$t->hasColumn('customer_id'))$t->addColumn('customer_id','bigint',['notnull'=>false]);
   if(!$t->hasIndex('re_erp_stock_customer'))$t->addIndex(['customer_id'],'re_erp_stock_customer');
  }
  return $schema;
 }
}
