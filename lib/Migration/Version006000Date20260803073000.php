<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version006000Date20260803073000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_team_events')){
   $t=$schema->getTable('re_erp_team_events');
   if(!$t->hasColumn('customer_id'))$t->addColumn('customer_id','bigint',['notnull'=>false]);
   if(!$t->hasColumn('project_id'))$t->addColumn('project_id','bigint',['notnull'=>false]);
   if(!$t->hasIndex('re_erp_event_project_start'))$t->addIndex(['project_id','start_at'],'re_erp_event_project_start');
  }
  if(!$schema->hasTable('re_erp_project_documents')){
   $t=$schema->createTable('re_erp_project_documents');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('project_id','bigint',['notnull'=>true]);
   $t->addColumn('customer_id','bigint',['notnull'=>true]);
   $t->addColumn('document_type','string',['length'=>32,'default'=>'other','notnull'=>true]);
   $t->addColumn('file_name','string',['length'=>255,'notnull'=>true]);
   $t->addColumn('file_path','string',['length'=>1024,'notnull'=>true]);
   $t->addColumn('mime_type','string',['length'=>128,'notnull'=>false]);
   $t->addColumn('status','string',['length'=>32,'default'=>'uploaded','notnull'=>true]);
   $t->addColumn('source','string',['length'=>32,'default'=>'manual','notnull'=>true]);
   $t->addColumn('extracted_text','text',['notnull'=>false]);
   $t->addColumn('metadata_json','text',['notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addIndex(['project_id','document_type'],'re_erp_project_document_type');
   $t->addIndex(['customer_id','created_at'],'re_erp_project_document_customer');
  }
  return $schema;
 }
}
