<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024011Date20260906083000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_checklist')){
   $table=$schema->createTable('re_erp_checklist');
   $table->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $table->addColumn('project_id','bigint',['notnull'=>true]);
   $table->addColumn('text','text',['notnull'=>true]);
   $table->addColumn('done','boolean',['notnull'=>false,'default'=>0]);
   $table->addColumn('client_id','string',['length'=>96,'notnull'=>false]);
   $table->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $table->addColumn('created_at','datetime',['notnull'=>true]);
   $table->addColumn('updated_at','datetime',['notnull'=>true]);
   $table->setPrimaryKey(['id']);
   $table->addIndex(['project_id','done','id'],'re_erp_checklist_project');
   $table->addUniqueIndex(['project_id','client_id'],'re_erp_checklist_client');
  }
  return $schema;
 }
}
