<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version001000Date20260803010000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if($s->hasTable('re_erp_workday_entries')){
   $t=$s->getTable('re_erp_workday_entries');
   if(!$t->hasColumn('billing_status'))$t->addColumn('billing_status','string',['length'=>24,'default'=>'open','notnull'=>true]);
   if(!$t->hasColumn('billing_rate'))$t->addColumn('billing_rate','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('billing_reference'))$t->addColumn('billing_reference','string',['length'=>100,'notnull'=>false]);
   if(!$t->hasColumn('billed_at'))$t->addColumn('billed_at','datetime',['notnull'=>false]);
   if(!$t->hasColumn('billed_by'))$t->addColumn('billed_by','string',['length'=>64,'notnull'=>false]);
   if(!$t->hasIndex('re_erp_wde_billing'))$t->addIndex(['billing_status','customer_id','project_id'],'re_erp_wde_billing');
  }
  return $s;
 }
}
