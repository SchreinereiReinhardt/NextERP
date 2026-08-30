<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version020209Date20260829170000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_customers')) return $schema;
  $t=$schema->getTable('re_erp_customers');
  $cols=[
   'customer_type'=>['length'=>20,'default'=>'business'],
   'invoice_email'=>['length'=>255],
   'vat_id'=>['length'=>64],
   'tax_no'=>['length'=>64],
   'leitweg_id'=>['length'=>100],
   'supplier_no'=>['length'=>100],
   'buyer_reference'=>['length'=>255],
   'purchase_order_reference'=>['length'=>255],
   'cost_center'=>['length'=>100],
   'invoice_format'=>['length'=>20,'default'=>'pdf'],
  ];
  foreach($cols as $name=>$opts){if(!$t->hasColumn($name)){$def=['notnull'=>false]+$opts;$t->addColumn($name,'string',$def);}}
  return $schema;
 }
}
