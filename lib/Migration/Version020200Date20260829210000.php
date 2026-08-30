<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020200Date20260829210000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if ($schema->hasTable('re_erp_settings')) {
   $t=$schema->getTable('re_erp_settings');
   foreach ([
    'bank1_name'=>['string',['length'=>190,'notnull'=>false]],
    'bank1_iban'=>['string',['length'=>64,'notnull'=>false]],
    'bank1_bic'=>['string',['length'=>32,'notnull'=>false]],
    'bank2_name'=>['string',['length'=>190,'notnull'=>false]],
    'bank2_iban'=>['string',['length'=>64,'notnull'=>false]],
    'bank2_bic'=>['string',['length'=>32,'notnull'=>false]],
    'default_offer_intro'=>['text',['notnull'=>false]],
    'default_offer_outro'=>['text',['notnull'=>false]],
    'default_invoice_note'=>['text',['notnull'=>false]],
   ] as $n=>[$type,$opts]) if(!$t->hasColumn($n)) $t->addColumn($n,$type,$opts);
  }
  return $schema;
 }
}
