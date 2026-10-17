<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version030003Date20261017150000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if($schema->hasTable('re_erp_customers')){
   $t=$schema->getTable('re_erp_customers');
   foreach([
    'salutation'=>['length'=>40,'notnull'=>false],
    'title'=>['length'=>80,'notnull'=>false],
    'first_name'=>['length'=>120,'notnull'=>false],
    'last_name'=>['length'=>120,'notnull'=>false],
    'address_addition'=>['length'=>255,'notnull'=>false],
   ] as $name=>$def){if(!$t->hasColumn($name))$t->addColumn($name,'string',$def);}
  }
  return $schema;
 }
}
