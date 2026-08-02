<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000100Date20260802100000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_customers')) {
   $t=$schema->createTable('re_erp_customers');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('name','string',['length'=>255,'notnull'=>true]);
   $t->addColumn('contact_name','string',['length'=>255,'notnull'=>false]);
   $t->addColumn('phone','string',['length'=>100,'notnull'=>false]);
   $t->addColumn('email','string',['length'=>255,'notnull'=>false]);
   $t->addColumn('address','text',['notnull'=>false]);
   $t->addColumn('notes','text',['notnull'=>false]);
   $t->addColumn('is_archived','boolean',['default'=>false,'notnull'=>true]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']); $t->addIndex(['name'],'re_erp_customer_name');
  }
  if(!$schema->hasTable('re_erp_projects')) {
   $t=$schema->createTable('re_erp_projects');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('customer_id','bigint',['notnull'=>true]);
   $t->addColumn('project_no','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('title','string',['length'=>255,'notnull'=>true]);
   $t->addColumn('status','string',['length'=>50,'default'=>'offen','notnull'=>true]);
   $t->addColumn('start_date','date',['notnull'=>false]);
   $t->addColumn('due_date','date',['notnull'=>false]);
   $t->addColumn('description','text',['notnull'=>false]);
   $t->addColumn('is_archived','boolean',['default'=>false,'notnull'=>true]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']); $t->addUniqueIndex(['project_no'],'re_erp_project_no'); $t->addIndex(['customer_id'],'re_erp_project_customer');
  }
  return $schema;
 }
}
