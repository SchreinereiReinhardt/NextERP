<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure; use OCP\DB\ISchemaWrapper; use OCP\Migration\IOutput; use OCP\Migration\SimpleMigrationStep;
final class Version020205Date20260829153500 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output,Closure $schemaClosure,array $options):?ISchemaWrapper{
  $schema=$schemaClosure();
  foreach(['re_erp_invoices','re_erp_offers'] as $name){
   if(!$schema->hasTable($name))continue;
   $t=$schema->getTable($name);
   if(!$t->hasColumn('clerk_name'))$t->addColumn('clerk_name','string',['length'=>190,'notnull'=>false]);
  }
  return $schema;
 }
}
