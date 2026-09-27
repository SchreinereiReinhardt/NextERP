<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024073Date20260927080000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_documents')){
   $t=$schema->getTable('re_erp_documents');
   if(!$t->hasColumn('cost_category'))$t->addColumn('cost_category','string',['length'=>32,'notnull'=>false]);
   if(!$t->hasColumn('project_supplier_id'))$t->addColumn('project_supplier_id','bigint',['notnull'=>false]);
   if(!$t->hasIndex('re_erp_docs_proj_supplier'))$t->addIndex(['project_supplier_id'],'re_erp_docs_proj_supplier');
   if(!$t->hasIndex('re_erp_docs_proj_cost'))$t->addIndex(['project_id','cost_category'],'re_erp_docs_proj_cost');
  }
  return $schema;
 }
}
