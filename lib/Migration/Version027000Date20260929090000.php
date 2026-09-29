<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version027000Date20260929090000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if($s->hasTable('re_erp_user_roles')){$t=$s->getTable('re_erp_user_roles');if(!$t->hasColumn('annual_vacation_days'))$t->addColumn('annual_vacation_days','decimal',['precision'=>5,'scale'=>2,'notnull'=>false]);}
  if(!$s->hasTable('re_erp_absences')){$t=$s->createTable('re_erp_absences');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);$t->addColumn('type','string',['length'=>24,'notnull'=>true]);$t->addColumn('date_from','date',['notnull'=>true]);$t->addColumn('date_to','date',['notnull'=>true]);$t->addColumn('status','string',['length'=>16,'default'=>'pending','notnull'=>true]);$t->addColumn('note','text',['notnull'=>false]);$t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);$t->addColumn('approved_by','string',['length'=>64,'notnull'=>false]);$t->addColumn('approved_at','datetime',['notnull'=>false]);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->addColumn('updated_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id'],'re_erp_abs_pk');$t->addIndex(['user_id','date_from'],'re_erp_abs_usr');$t->addIndex(['status','date_from'],'re_erp_abs_stat');}
  return $s;
 }
}
