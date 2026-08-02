<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version000800Date20260802213000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_user_roles')){$t=$schema->getTable('re_erp_user_roles');if(!$t->hasColumn('enabled'))$t->addColumn('enabled','boolean',['default'=>true,'notnull'=>true]);}
  if($schema->hasTable('re_erp_workdays')){$t=$schema->getTable('re_erp_workdays');if(!$t->hasColumn('entered_by'))$t->addColumn('entered_by','string',['length'=>64,'notnull'=>false]);if(!$t->hasColumn('updated_by'))$t->addColumn('updated_by','string',['length'=>64,'notnull'=>false]);if(!$t->hasColumn('updated_at'))$t->addColumn('updated_at','datetime',['notnull'=>false]);}
  return $schema;
 }
}
