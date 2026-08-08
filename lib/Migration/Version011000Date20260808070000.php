<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version011000Date20260808070000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_documents')){
   $t=$schema->getTable('re_erp_documents');
   if(!$t->hasColumn('order_id')){$t->addColumn('order_id','bigint',['notnull'=>false]);$t->addIndex(['order_id','document_type'],'re_erp_docs_order_type');}
  }
  return $schema;
 }
}
