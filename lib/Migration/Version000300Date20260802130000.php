<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000300Date20260802130000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if(!$s->hasTable('re_erp_sequences')){
   $t=$s->createTable('re_erp_sequences');
   $t->addColumn('sequence_key','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('current_value','bigint',['default'=>0,'notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['sequence_key']);
  }
  if($s->hasTable('re_erp_reports')){
   $t=$s->getTable('re_erp_reports');
   if(!$t->hasColumn('signature_data'))$t->addColumn('signature_data','text',['notnull'=>false]);
   if(!$t->hasColumn('signature_mime'))$t->addColumn('signature_mime','string',['length'=>64,'notnull'=>false]);
  }
  if($s->hasTable('re_erp_workday_entries')){
   $t=$s->getTable('re_erp_workday_entries');
   if(!$t->hasColumn('imported_to_report_id'))$t->addColumn('imported_to_report_id','bigint',['notnull'=>false]);
   if(!$t->hasColumn('created_at'))$t->addColumn('created_at','datetime',['notnull'=>false]);
  }
  return $s;
 }
}
