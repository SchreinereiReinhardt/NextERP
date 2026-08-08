<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version010002Date20260807155000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_reports')){
   $table=$schema->getTable('re_erp_reports');
   if(!$table->hasColumn('archived'))$table->addColumn('archived','boolean',['default'=>false,'notnull'=>true]);
   if(!$table->hasColumn('archived_at'))$table->addColumn('archived_at','datetime',['notnull'=>false]);
   if(!$table->hasColumn('archived_by'))$table->addColumn('archived_by','string',['length'=>64,'notnull'=>false]);
   if(!$table->hasIndex('re_erp_report_archived'))$table->addIndex(['archived'],'re_erp_report_archived');
  }
  return $schema;
 }
}
