<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Recovery migration for development builds where the app version 2.10.19
 * was already registered before the text-block schema migration was added.
 * Idempotent by design.
 */
final class Version029016Date20261006133000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();

  if($schema->hasTable('re_erp_checklist')){
   $t=$schema->getTable('re_erp_checklist');
   if(!$t->hasColumn('completed_at')){
    $t->addColumn('completed_at','datetime',['notnull'=>false]);
   }
  }

  if(!$schema->hasTable('re_erp_offer_text_blocks')){
   $t=$schema->createTable('re_erp_offer_text_blocks');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('title','string',['length'=>150,'notnull'=>true]);
   $t->addColumn('text','text',['notnull'=>true]);
   $t->addColumn('unit','string',['length'=>30,'notnull'=>false]);
   $t->addColumn('unit_price','decimal',['precision'=>12,'scale'=>2,'notnull'=>false]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id'],'re_erp_otb_pk');
   $t->addIndex(['title'],'re_erp_otb_title');
  }

  return $schema;
 }
}
