<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024026Date20260925143000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_invoice_audit')){
   $t=$schema->createTable('re_erp_invoice_audit');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('invoice_id','bigint',['notnull'=>true]);
   $t->addColumn('event_type','string',['length'=>48,'notnull'=>true]);
   $t->addColumn('user_id','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('event_at','datetime',['notnull'=>true]);
   $t->addColumn('details','text',['notnull'=>false]);
   $t->addColumn('snapshot_hash','string',['length'=>64,'notnull'=>false]);
   $t->setPrimaryKey(['id'],'re_erp_inv_audit_pk');
   $t->addIndex(['invoice_id','event_at'],'re_erp_inv_audit_evt');
  }
  return $schema;
 }
}
