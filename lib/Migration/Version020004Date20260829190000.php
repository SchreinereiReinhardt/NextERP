<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020004Date20260829190000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_invoices')){
   $t=$schema->getTable('re_erp_invoices');
   foreach([
    'reminder_level'=>['integer',['notnull'=>true,'default'=>0]],
    'last_reminder_at'=>['datetime',['notnull'=>false]],
   ] as $n=>[$type,$opts]) if(!$t->hasColumn($n)) $t->addColumn($n,$type,$opts);
  }
  if(!$schema->hasTable('re_erp_invoice_payments')){
   $t=$schema->createTable('re_erp_invoice_payments');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('invoice_id','bigint',['notnull'=>true]);
   $t->addColumn('payment_date','date',['notnull'=>true]);
   $t->addColumn('amount','decimal',['precision'=>15,'scale'=>2,'notnull'=>true]);
   $t->addColumn('note','string',['length'=>512,'notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'], 're_erp_inv_pay_pk');
   $t->addIndex(['invoice_id','payment_date'],'re_erp_payment_invoice_date');
  }
  return $schema;
 }
}
