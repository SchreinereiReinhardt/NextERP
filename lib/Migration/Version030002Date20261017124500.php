<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version030002Date20261017124500 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_invoices')){
   $t=$schema->getTable('re_erp_invoices');
   if(!$t->hasColumn('currency'))$t->addColumn('currency','string',['length'=>3,'default'=>'EUR','notnull'=>true]);
  }
  return $schema;
 }
}
