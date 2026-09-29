<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version026000Date20260929073000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if($s->hasTable('re_erp_user_roles')){$t=$s->getTable('re_erp_user_roles');if(!$t->hasColumn('target_weekly_hours'))$t->addColumn('target_weekly_hours','decimal',['precision'=>5,'scale'=>2,'notnull'=>false]);}
  if(!$s->hasTable('re_erp_attendance')){$t=$s->createTable('re_erp_attendance');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);$t->addColumn('work_date','date',['notnull'=>true]);$t->addColumn('clock_in','datetime',['notnull'=>true]);$t->addColumn('clock_out','datetime',['notnull'=>false]);$t->addColumn('break_started_at','datetime',['notnull'=>false]);$t->addColumn('break_minutes','integer',['default'=>0,'notnull'=>true]);$t->addColumn('status','string',['length'=>16,'default'=>'working','notnull'=>true]);$t->addColumn('created_at','datetime',['notnull'=>true]);$t->addColumn('updated_at','datetime',['notnull'=>true]);$t->setPrimaryKey(['id'],'re_erp_att_pk');$t->addIndex(['user_id','work_date'],'re_erp_att_user_day');}
  if(!$s->hasTable('re_erp_attendance_audit')){$t=$s->createTable('re_erp_attendance_audit');$t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);$t->addColumn('attendance_id','bigint',['notnull'=>false]);$t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);$t->addColumn('action','string',['length'=>24,'notnull'=>true]);$t->addColumn('event_at','datetime',['notnull'=>true]);$t->addColumn('performed_by','string',['length'=>64,'notnull'=>true]);$t->addColumn('note','text',['notnull'=>false]);$t->setPrimaryKey(['id'],'re_erp_att_aud_pk');$t->addIndex(['user_id','event_at'],'re_erp_att_aud_usr');}
  return $s;
 }
}
