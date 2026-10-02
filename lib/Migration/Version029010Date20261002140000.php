<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version029010Date20261002140000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_documents')){
   $t=$schema->getTable('re_erp_documents');
   if(!$t->hasColumn('extracted_text'))$t->addColumn('extracted_text','text',['notnull'=>false]);
   if(!$t->hasColumn('ocr_status'))$t->addColumn('ocr_status','string',['length'=>24,'notnull'=>false]);
   if(!$t->hasColumn('ocr_task_id'))$t->addColumn('ocr_task_id','bigint',['notnull'=>false]);
  }
  return $schema;
 }
}
