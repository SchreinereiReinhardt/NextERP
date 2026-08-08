<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version010003Date20260808060000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_project_users')){
   $table=$schema->getTable('re_erp_project_users');
   if(!$table->hasColumn('folder_permissions'))$table->addColumn('folder_permissions','text',['notnull'=>false]);
  }
  return $schema;
 }
}
