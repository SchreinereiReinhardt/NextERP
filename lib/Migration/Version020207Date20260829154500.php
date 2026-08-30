<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020207Date20260829154500 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output,Closure $schemaClosure,array $options):?ISchemaWrapper{
  $schema=$schemaClosure();
  foreach(['re_erp_invoices','re_erp_offers'] as $name){
   if(!$schema->hasTable($name))continue;
   $t=$schema->getTable($name);
   foreach([
    'subject'=>['string',['length'=>255,'notnull'=>false]],
    'intro_text'=>['text',['notnull'=>false]],
    'outro_text'=>['text',['notnull'=>false]],
   ] as $column=>[$type,$opts]){
    if(!$t->hasColumn($column))$t->addColumn($column,$type,$opts);
   }
  }
  return $schema;
 }
}
