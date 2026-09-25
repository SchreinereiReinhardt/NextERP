<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024028Date20260925160000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();if($schema->hasTable('re_erp_customers')){$t=$schema->getTable('re_erp_customers');if(!$t->hasColumn('datev_debtor_account'))$t->addColumn('datev_debtor_account','string',['length'=>16,'notnull'=>false]);}return $schema;
 }
}
