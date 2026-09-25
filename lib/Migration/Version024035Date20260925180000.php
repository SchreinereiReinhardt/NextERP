<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024035Date20260925180000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_cash_entries')){
   $t=$schema->createTable('re_erp_cash_entries');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true,'unsigned'=>true]);
   $t->addColumn('entry_date','date',['notnull'=>true]);
   $t->addColumn('entry_type','string',['length'=>16,'notnull'=>true]);
   $t->addColumn('amount','decimal',['precision'=>12,'scale'=>2,'notnull'=>true]);
   $t->addColumn('vat_rate','decimal',['precision'=>5,'scale'=>2,'notnull'=>false]);
   $t->addColumn('category','string',['length'=>120,'notnull'=>false]);
   $t->addColumn('description','string',['length'=>500,'notnull'=>true]);
   $t->addColumn('receipt_no','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('cancelled_at','datetime',['notnull'=>false]);
   $t->addColumn('cancel_reason','string',['length'=>500,'notnull'=>false]);
   $t->setPrimaryKey(['id'],'re_cash_pk');
   $t->addIndex(['entry_date'],'re_cash_date');
  }
  return $schema;
 }
}
