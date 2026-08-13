<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version015300Date20260810073000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options):?ISchemaWrapper{
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_suppliers'))return null;
  $t=$schema->getTable('re_erp_suppliers');
  foreach([
   'street'=>['string',['length'=>200,'notnull'=>false]],
   'postal_code'=>['string',['length'=>20,'notnull'=>false]],
   'city'=>['string',['length'=>120,'notnull'=>false]],
   'country'=>['string',['length'=>120,'notnull'=>false]],
   'website'=>['string',['length'=>255,'notnull'=>false]],
   'iban'=>['string',['length'=>64,'notnull'=>false]],
   'bic'=>['string',['length'=>32,'notnull'=>false]],
   'payment_terms'=>['string',['length'=>120,'notnull'=>false]],
  ] as $name=>[$type,$opts]){
   if(!$t->hasColumn($name))$t->addColumn($name,$type,$opts);
  }
  return $schema;
 }
}