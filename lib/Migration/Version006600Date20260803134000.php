<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version006600Date20260803134000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_mobile_tokens')){
   $t=$schema->createTable('re_erp_mobile_tokens');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('token_hash','string',['length'=>128,'notnull'=>true]);
   $t->addColumn('refresh_hash','string',['length'=>128,'notnull'=>true]);
   $t->addColumn('device_name','string',['length'=>160,'notnull'=>false]);
   $t->addColumn('expires_at','datetime',['notnull'=>true]);
   $t->addColumn('refresh_expires_at','datetime',['notnull'=>true]);
   $t->addColumn('last_used_at','datetime',['notnull'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('revoked_at','datetime',['notnull'=>false]);
   $t->setPrimaryKey(['id']);
   $t->addUniqueIndex(['token_hash'],'re_erp_mobile_token_hash');
   $t->addUniqueIndex(['refresh_hash'],'re_erp_mobile_refresh_hash');
   $t->addIndex(['user_id','revoked_at'],'re_erp_mobile_user_active');
  }
  return $schema;
 }
}
