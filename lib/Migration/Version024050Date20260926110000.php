<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version024050Date20260926110000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options):?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_mobile_sync_receipts')){
   $t=$schema->createTable('re_erp_mobile_sync_receipts');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('operation','string',['length'=>32,'notnull'=>true]);
   $t->addColumn('client_id','string',['length'=>96,'notnull'=>true]);
   $t->addColumn('status','string',['length'=>16,'notnull'=>true,'default'=>'processing']);
   $t->addColumn('result_json','text',['notnull'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addUniqueIndex(['user_id','operation','client_id'],'re_erp_mobile_sync_unique');
   $t->addIndex(['updated_at'],'re_erp_mobile_sync_updated');
  }
  return $schema;
 }
}
