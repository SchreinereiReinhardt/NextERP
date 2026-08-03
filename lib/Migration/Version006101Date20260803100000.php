<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;
use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
final class Version006101Date20260803100000 extends SimpleMigrationStep {
 public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
  $schema=$schemaClosure();
  if(!$schema->hasTable('re_erp_documents')){
   $t=$schema->createTable('re_erp_documents');
   $t->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
   $t->addColumn('file_id','bigint',['notnull'=>false]);
   $t->addColumn('file_name','string',['length'=>255,'notnull'=>true]);
   $t->addColumn('original_name','string',['length'=>255,'notnull'=>true]);
   $t->addColumn('file_path','string',['length'=>1024,'notnull'=>true]);
   $t->addColumn('mime_type','string',['length'=>128,'notnull'=>false]);
   $t->addColumn('file_size','bigint',['default'=>0,'notnull'=>true]);
   $t->addColumn('checksum','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('document_type','string',['length'=>48,'default'=>'unassigned','notnull'=>true]);
   $t->addColumn('status','string',['length'=>32,'default'=>'unassigned','notnull'=>true]);
   $t->addColumn('customer_id','bigint',['notnull'=>false]);
   $t->addColumn('project_id','bigint',['notnull'=>false]);
   $t->addColumn('supplier_id','bigint',['notnull'=>false]);
   $t->addColumn('document_no','string',['length'=>128,'notnull'=>false]);
   $t->addColumn('document_date','date',['notnull'=>false]);
   $t->addColumn('due_date','date',['notnull'=>false]);
   $t->addColumn('net_amount','decimal',['precision'=>15,'scale'=>2,'notnull'=>false]);
   $t->addColumn('vat_amount','decimal',['precision'=>15,'scale'=>2,'notnull'=>false]);
   $t->addColumn('gross_amount','decimal',['precision'=>15,'scale'=>2,'notnull'=>false]);
   $t->addColumn('currency','string',['length'=>3,'default'=>'EUR','notnull'=>true]);
   $t->addColumn('notes','text',['notnull'=>false]);
   $t->addColumn('source','string',['length'=>32,'default'=>'upload','notnull'=>true]);
   $t->addColumn('created_by','string',['length'=>64,'notnull'=>true]);
   $t->addColumn('assigned_by','string',['length'=>64,'notnull'=>false]);
   $t->addColumn('created_at','datetime',['notnull'=>true]);
   $t->addColumn('assigned_at','datetime',['notnull'=>false]);
   $t->addColumn('updated_at','datetime',['notnull'=>true]);
   $t->setPrimaryKey(['id']);
   $t->addIndex(['status','created_at'],'re_erp_docs_status_created');
   $t->addIndex(['document_type','document_date'],'re_erp_docs_type_date');
   $t->addIndex(['project_id','document_type'],'re_erp_docs_project_type');
   $t->addIndex(['supplier_id','document_no'],'re_erp_docs_supplier_no');
   $t->addIndex(['checksum'],'re_erp_docs_checksum');
  }
  return $schema;
 }
}
