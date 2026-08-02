<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000900Date20260802230000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_time_timers')){
   $t=$s->createTable('re_erp_time_timers');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('project_id','bigint',['notnull'=>true]);
   $t->addColumn('activity','text',['notnull'=>true]);
   $t->addColumn('started_at','datetime',['notnull'=>true]);
   $t->addColumn('paused_at','datetime',['notnull'=>false]);
   $t->addColumn('pause_seconds','bigint',['default'=>0,'notnull'=>true]);
   $t->addColumn('status','string',['length'=>16,'default'=>'running','notnull'=>true]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addIndex(['status','user_id'],'re_erp_timer_status_user');
  }
  return $s;
 }
}
