<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version024069Date20260927080000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output,Closure $schemaClosure,array $options):?ISchemaWrapper{
  $s=$schemaClosure();
  if($s->hasTable('re_erp_invoices')){
   $t=$s->getTable('re_erp_invoices');
   if(!$t->hasColumn('labor_cost_gross'))$t->addColumn('labor_cost_gross','decimal',['precision'=>14,'scale'=>2,'notnull'=>false]);
  }
  return $s;
 }
}
