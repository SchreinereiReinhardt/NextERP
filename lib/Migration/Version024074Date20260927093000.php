<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024074Date20260927093000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_offer_items')){$t=$schema->getTable('re_erp_offer_items');
   if(!$t->hasColumn('planned_hours'))$t->addColumn('planned_hours','decimal',['precision'=>10,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('planned_material_cost'))$t->addColumn('planned_material_cost','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   if(!$t->hasColumn('planned_external_cost'))$t->addColumn('planned_external_cost','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
  }
  if($schema->hasTable('re_erp_user_roles')){$t=$schema->getTable('re_erp_user_roles');if(!$t->hasColumn('internal_cost_rate'))$t->addColumn('internal_cost_rate','decimal',['precision'=>10,'scale'=>2,'notnull'=>false]);}
  return $schema;
 }
}
