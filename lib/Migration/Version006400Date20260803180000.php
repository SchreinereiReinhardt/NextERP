<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version006400Date20260803180000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_documents')){
   $table=$schema->getTable('re_erp_documents');
   if(!$table->hasColumn('suggestion_reason')){$table->addColumn('suggestion_reason','text',['notnull'=>false]);}
  }
  return $schema;
 }
}
