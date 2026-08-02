<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000600Date20260802170000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $s=$schemaClosure();
  if($s->hasTable('re_erp_reports')){$t=$s->getTable('re_erp_reports');
   if(!$t->hasColumn('locked'))$t->addColumn('locked','boolean',['default'=>false,'notnull'=>true]);
   if(!$t->hasColumn('customer_note'))$t->addColumn('customer_note','text',['notnull'=>false]);
  }
  if($s->hasTable('re_erp_report_hours')){$t=$s->getTable('re_erp_report_hours');
   if(!$t->hasColumn('work_date'))$t->addColumn('work_date','date',['notnull'=>false]);
   if(!$t->hasColumn('source_entry_id'))$t->addColumn('source_entry_id','bigint',['notnull'=>false]);
  }
  if($s->hasTable('re_erp_report_items')){$t=$s->getTable('re_erp_report_items');
   if(!$t->hasColumn('notes'))$t->addColumn('notes','text',['notnull'=>false]);
  }
  return $s;
 }
}
