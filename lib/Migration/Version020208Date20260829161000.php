<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020208Date20260829161000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_invoices')){
   $t=$schema->getTable('re_erp_invoices');
   if(!$t->hasColumn('order_id'))$t->addColumn('order_id','integer',['notnull'=>false]);
   if(!$t->hasColumn('installment_percent'))$t->addColumn('installment_percent','decimal',['precision'=>7,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('installment_base_net'))$t->addColumn('installment_base_net','decimal',['precision'=>14,'scale'=>2,'notnull'=>false]);
  }
  return $schema;
 }
}
