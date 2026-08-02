<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version001100Date20260803030000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_hourly_rates')){
   $t=$s->createTable('re_erp_hourly_rates');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('name','string',['length'=>100,'notnull'=>true]);
   $t->addColumn('code','string',['length'=>32,'notnull'=>true]);
   $t->addColumn('sales_rate','decimal',['precision'=>12,'scale'=>2,'default'=>0,'notnull'=>true]);
   $t->addColumn('cost_rate','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   $t->addColumn('valid_from','date',['notnull'=>false]);
   $t->addColumn('active','boolean',['default'=>true,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addUniqueIndex(['code'],'re_erp_hourly_rate_code');
  }
  if($s->hasTable('re_erp_user_roles')){
   $t=$s->getTable('re_erp_user_roles');
   if(!$t->hasColumn('hourly_rate_id'))$t->addColumn('hourly_rate_id','bigint',['notnull'=>false]);
   if(!$t->hasColumn('individual_hourly_rate'))$t->addColumn('individual_hourly_rate','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
  }
  if($s->hasTable('re_erp_projects')){
   $t=$s->getTable('re_erp_projects');
   if(!$t->hasColumn('special_hourly_rate'))$t->addColumn('special_hourly_rate','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
  }
  return $s;
 }
}
