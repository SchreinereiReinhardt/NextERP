<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024076Date20260927110000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_projects')){$t=$schema->getTable('re_erp_projects');
   if(!$t->hasColumn('calc_value_net'))$t->addColumn('calc_value_net','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('calc_hours'))$t->addColumn('calc_hours','decimal',['precision'=>10,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('calc_material'))$t->addColumn('calc_material','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('calc_external'))$t->addColumn('calc_external','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('calc_locked_at'))$t->addColumn('calc_locked_at','datetime',['notnull'=>false]);
  }
  return $schema;
 }
}
