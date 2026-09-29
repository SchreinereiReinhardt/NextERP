<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version027006Date20260929130000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if($s->hasTable('re_erp_user_roles')){$t=$s->getTable('re_erp_user_roles');
   foreach(['monday_hours'=>8,'tuesday_hours'=>8,'wednesday_hours'=>8,'thursday_hours'=>8,'friday_hours'=>8,'saturday_hours'=>0,'sunday_hours'=>0] as $c=>$d)if(!$t->hasColumn($c))$t->addColumn($c,'decimal',['precision'=>5,'scale'=>2,'notnull'=>false,'default'=>$d]);
   if(!$t->hasColumn('time_account_start_date'))$t->addColumn('time_account_start_date','date',['notnull'=>false]);
   if(!$t->hasColumn('time_account_start_balance'))$t->addColumn('time_account_start_balance','decimal',['precision'=>8,'scale'=>2,'notnull'=>false,'default'=>0]);
  }
  return $s;
 }
}
