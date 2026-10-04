<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version029013Date20261004120000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_project_phases')){
   $t=$schema->createTable('re_erp_project_phases');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('name','string',['length'=>100,'notnull'=>true]);
   $t->addColumn('sort_order','integer',['notnull'=>true,'default'=>0]);
   $t->addColumn('active','boolean',['notnull'=>false,'default'=>true]);
   $t->addColumn('freeze_calculation','boolean',['notnull'=>false,'default'=>false]);
   $t->addColumn('closes_project','boolean',['notnull'=>false,'default'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_pphase_pk');
   $t->addIndex(['sort_order'],'re_erp_pphase_sort');
  }
  return $schema;
 }
}
