<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version020316Date20260830080000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  foreach (['re_erp_offer_items','re_erp_invoice_items'] as $tableName) {
   if (!$schema->hasTable($tableName)) continue;
   $t=$schema->getTable($tableName);
   if(!$t->hasColumn('image_name'))$t->addColumn('image_name','string',['length'=>255,'notnull'=>false]);
   if(!$t->hasColumn('image_mime'))$t->addColumn('image_mime','string',['length'=>80,'notnull'=>false]);
   if(!$t->hasColumn('image_data'))$t->addColumn('image_data','text',['notnull'=>false,'length'=>16777215]);
  }
  return $schema;
 }
}
