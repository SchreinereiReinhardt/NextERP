<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version006300Date20260803153000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('re_erp_documents')) {
            $table=$schema->getTable('re_erp_documents');
            foreach ([
                'processing_status'=>['string',['length'=>32,'default'=>'new','notnull'=>true]],
                'detected_at'=>['datetime',['notnull'=>false]],
                'last_seen_at'=>['datetime',['notnull'=>false]],
                'auto_rule_id'=>['bigint',['notnull'=>false]],
            ] as $name=>[$type,$definition]) {
                if (!$table->hasColumn($name)) { $table->addColumn($name,$type,$definition); }
            }
            if (!$table->hasIndex('re_erp_docs_processing')) { $table->addIndex(['processing_status','created_at'],'re_erp_docs_processing'); }
        }
        if (!$schema->hasTable('re_erp_document_rules')) {
            $table=$schema->createTable('re_erp_document_rules');
            $table->addColumn('id','bigint',['autoincrement'=>true,'notnull'=>true]);
            $table->addColumn('name','string',['length'=>190,'notnull'=>true]);
            $table->addColumn('enabled','boolean',['default'=>true,'notnull'=>true]);
            $table->addColumn('priority','integer',['default'=>100,'notnull'=>true]);
            $table->addColumn('match_field','string',['length'=>64,'default'=>'filename','notnull'=>true]);
            $table->addColumn('match_operator','string',['length'=>32,'default'=>'contains','notnull'=>true]);
            $table->addColumn('match_value','string',['length'=>255,'notnull'=>true]);
            $table->addColumn('document_type','string',['length'=>48,'notnull'=>false]);
            $table->addColumn('customer_id','bigint',['notnull'=>false]);
            $table->addColumn('project_id','bigint',['notnull'=>false]);
            $table->addColumn('supplier_id','bigint',['notnull'=>false]);
            $table->addColumn('created_at','datetime',['notnull'=>true]);
            $table->addColumn('updated_at','datetime',['notnull'=>true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['enabled','priority'],'re_erp_rules_active');
        }
        return $schema;
    }
}
