<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version000200Date20260802113000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if ($schema->hasTable('re_erp_customers')) {
   $t=$schema->getTable('re_erp_customers');
   if (!$t->hasColumn('customer_no')) $t->addColumn('customer_no','string',['length'=>64,'notnull'=>false]);
   if (!$t->hasColumn('folder_path')) $t->addColumn('folder_path','string',['length'=>1024,'notnull'=>false]);
  }
  if ($schema->hasTable('re_erp_projects')) {
   $t=$schema->getTable('re_erp_projects');
   if (!$t->hasColumn('folder_path')) $t->addColumn('folder_path','string',['length'=>1024,'notnull'=>false]);
  }
  $this->createReports($schema);
  $this->createReportItems($schema);
  $this->createReportHours($schema);
  $this->createReportFiles($schema);
  $this->createMaterials($schema);
  $this->createWorkdays($schema);
  $this->createWorkdayEntries($schema);
  $this->createTeamEvents($schema);
  $this->createProjectUsers($schema);
  return $schema;
 }
 private function createReports(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_reports')) return; $t=$s->createTable('re_erp_reports');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
  $t->addColumn('project_id','bigint',['notnull'=>true]);
  $t->addColumn('report_no','string',['length'=>64,'notnull'=>true]);
  $t->addColumn('report_date','date',['notnull'=>true]);
  $t->addColumn('title','string',['length'=>255,'notnull'=>true]);
  $t->addColumn('description','text',['notnull'=>false]);
  $t->addColumn('status','string',['length'=>32,'default'=>'Entwurf','notnull'=>true]);
  $t->addColumn('signed_by','string',['length'=>255,'notnull'=>false]);
  $t->addColumn('signed_at','datetime',['notnull'=>false]);
  $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
  $t->addColumn('created_at','datetime',['notnull'=>true]);
  $t->addColumn('updated_at','datetime',['notnull'=>true]);
  $t->setPrimaryKey(['id']); $t->addUniqueIndex(['report_no'],'re_erp_report_no'); $t->addIndex(['project_id'],'re_erp_report_project');
 }
 private function createReportItems(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_report_items')) return; $t=$s->createTable('re_erp_report_items');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('report_id','bigint',['notnull'=>true]);
  $t->addColumn('material_id','bigint',['notnull'=>false]); $t->addColumn('description','string',['length'=>1024,'notnull'=>true]);
  $t->addColumn('quantity','decimal',['precision'=>12,'scale'=>3,'default'=>0]); $t->addColumn('unit','string',['length'=>32,'notnull'=>false]);
  $t->setPrimaryKey(['id']); $t->addIndex(['report_id'],'re_erp_ri_report');
 }
 private function createReportHours(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_report_hours')) return; $t=$s->createTable('re_erp_report_hours');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('report_id','bigint',['notnull'=>true]);
  $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]); $t->addColumn('hours','decimal',['precision'=>8,'scale'=>2,'default'=>0]);
  $t->addColumn('activity','string',['length'=>255,'notnull'=>false]); $t->setPrimaryKey(['id']); $t->addIndex(['report_id'],'re_erp_rh_report');
 }
 private function createReportFiles(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_report_files')) return; $t=$s->createTable('re_erp_report_files');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('report_id','bigint',['notnull'=>true]);
  $t->addColumn('file_id','bigint',['notnull'=>false]); $t->addColumn('path','string',['length'=>1024,'notnull'=>true]);
  $t->addColumn('created_at','datetime',['notnull'=>true]); $t->setPrimaryKey(['id']); $t->addIndex(['report_id'],'re_erp_rf_report');
 }
 private function createMaterials(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_materials')) return; $t=$s->createTable('re_erp_materials');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('article_no','string',['length'=>100,'notnull'=>false]);
  $t->addColumn('name','string',['length'=>255,'notnull'=>true]); $t->addColumn('material_group','string',['length'=>255,'notnull'=>false]);
  $t->addColumn('unit','string',['length'=>32,'notnull'=>false]); $t->addColumn('price','decimal',['precision'=>12,'scale'=>2,'default'=>0]);
  $t->addColumn('active','boolean',['default'=>true,'notnull'=>true]); $t->addColumn('created_at','datetime',['notnull'=>true]);
  $t->setPrimaryKey(['id']); $t->addIndex(['name'],'re_erp_material_name');
 }
 private function createWorkdays(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_workdays')) return; $t=$s->createTable('re_erp_workdays');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]);
  $t->addColumn('work_date','date',['notnull'=>true]); $t->addColumn('start_time','string',['length'=>8,'notnull'=>false]);
  $t->addColumn('end_time','string',['length'=>8,'notnull'=>false]); $t->addColumn('break_minutes','integer',['default'=>0,'notnull'=>true]);
  $t->addColumn('notes','text',['notnull'=>false]); $t->addColumn('created_at','datetime',['notnull'=>true]);
  $t->setPrimaryKey(['id']); $t->addIndex(['user_id','work_date'],'re_erp_workday_user_date');
 }
 private function createWorkdayEntries(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_workday_entries')) return; $t=$s->createTable('re_erp_workday_entries');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('workday_id','bigint',['notnull'=>true]);
  $t->addColumn('customer_id','bigint',['notnull'=>false]); $t->addColumn('project_id','bigint',['notnull'=>false]);
  $t->addColumn('activity','string',['length'=>255,'notnull'=>true]); $t->addColumn('hours','decimal',['precision'=>8,'scale'=>2,'default'=>0]);
  $t->setPrimaryKey(['id']); $t->addIndex(['workday_id'],'re_erp_wde_workday');
 }
 private function createTeamEvents(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_team_events')) return; $t=$s->createTable('re_erp_team_events');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('title','string',['length'=>255,'notnull'=>true]);
  $t->addColumn('start_at','datetime',['notnull'=>true]); $t->addColumn('end_at','datetime',['notnull'=>false]);
  $t->addColumn('location','string',['length'=>255,'notnull'=>false]); $t->addColumn('description','text',['notnull'=>false]);
  $t->addColumn('calendar_uri','string',['length'=>255,'notnull'=>false]); $t->addColumn('calendar_object_uri','string',['length'=>255,'notnull'=>false]);
  $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]); $t->addColumn('created_at','datetime',['notnull'=>true]);
  $t->setPrimaryKey(['id']); $t->addIndex(['start_at'],'re_erp_event_start');
 }
 private function createProjectUsers(ISchemaWrapper $s): void {
  if($s->hasTable('re_erp_project_users')) return; $t=$s->createTable('re_erp_project_users');
  $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]); $t->addColumn('project_id','bigint',['notnull'=>true]);
  $t->addColumn('user_id','string',['length'=>64,'notnull'=>true]); $t->addColumn('role','string',['length'=>64,'default'=>'Mitarbeiter','notnull'=>true]);
  $t->setPrimaryKey(['id']); $t->addUniqueIndex(['project_id','user_id'],'re_erp_project_user_unique');
 }
}
