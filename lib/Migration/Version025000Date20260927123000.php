<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version025000Date20260927123000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_bank_accounts')){$t=$s->createTable('re_erp_bank_accounts');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('name','string',['length'=>160,'notnull'=>true]);$t->addColumn('iban','string',['length'=>64,'notnull'=>false]);$t->addColumn('bic','string',['length'=>32,'notnull'=>false]);$t->addColumn('bank_name','string',['length'=>160,'notnull'=>false]);$t->addColumn('provider','string',['length'=>32,'notnull'=>false]);$t->addColumn('active','boolean',['notnull'=>false,'default'=>1]);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id'],'re_erp_bank_acc_pk');}
  if(!$s->hasTable('re_erp_bank_transactions')){$t=$s->createTable('re_erp_bank_transactions');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('account_id','bigint',['notnull'=>false]);$t->addColumn('booking_date','date',['notnull'=>true]);$t->addColumn('value_date','date',['notnull'=>false]);$t->addColumn('amount','decimal',['precision'=>15,'scale'=>2,'notnull'=>true]);$t->addColumn('currency','string',['length'=>3,'notnull'=>true,'default'=>'EUR']);$t->addColumn('counterparty','string',['length'=>255,'notnull'=>false]);$t->addColumn('counterparty_iban','string',['length'=>64,'notnull'=>false]);$t->addColumn('purpose','text',['notnull'=>false]);$t->addColumn('external_id','string',['length'=>128,'notnull'=>true]);$t->addColumn('invoice_id','bigint',['notnull'=>false]);$t->addColumn('match_status','string',['length'=>24,'notnull'=>true,'default'=>'open']);$t->addColumn('import_source','string',['length'=>32,'notnull'=>true,'default'=>'csv']);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id'],'re_erp_bank_tx_pk');$t->addUniqueIndex(['external_id'],'re_erp_bank_tx_ext');$t->addIndex(['booking_date'],'re_erp_bank_tx_date');$t->addIndex(['invoice_id'],'re_erp_bank_tx_inv');}
  return $s;
 }
}
