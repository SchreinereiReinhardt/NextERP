<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version020315Date20260830074000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure(); if(!$schema->hasTable('re_erp_invoices')) return $schema; $t=$schema->getTable('re_erp_invoices');
  if(!$t->hasColumn('tax_mode')) $t->addColumn('tax_mode','string',['length'=>32,'notnull'=>true,'default'=>'standard19']);
  if(!$t->hasColumn('tax_note')) $t->addColumn('tax_note','text',['notnull'=>false]);
  if(!$t->hasColumn('payment_term_key')) $t->addColumn('payment_term_key','string',['length'=>64,'notnull'=>false]);
  return $schema;
 }
}
