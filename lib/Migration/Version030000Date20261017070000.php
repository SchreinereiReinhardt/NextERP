<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version030000Date20261017070000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_suppliers')){$t=$schema->getTable('re_erp_suppliers');if(!$t->hasColumn('datev_creditor_account'))$t->addColumn('datev_creditor_account','string',['length'=>16,'notnull'=>false]);}
  if($schema->hasTable('re_erp_documents')){$t=$schema->getTable('re_erp_documents');if(!$t->hasColumn('datev_expense_account'))$t->addColumn('datev_expense_account','string',['length'=>16,'notnull'=>false]);if(!$t->hasColumn('datev_vat_rate'))$t->addColumn('datev_vat_rate','decimal',['precision'=>5,'scale'=>2,'notnull'=>false]);}
  return $schema;
 }
}
