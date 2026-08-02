<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000700Date20260802190000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_user_roles')){
   $t=$schema->createTable('re_erp_user_roles');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('role','string',['length'=>32,'default'=>'employee','notnull'=>true]);
   $t->addColumn('updated_by','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addUniqueIndex(['user_id'],'re_erp_user_role_unique');
  }
  return $schema;
 }
}
