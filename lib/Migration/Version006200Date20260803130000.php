<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version006200Date20260803130000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_documents')) {
            return $schema;
        }

        $table = $schema->getTable('re_erp_documents');
        $columns = [
            'suggested_type' => ['string', ['length' => 48, 'notnull' => false]],
            'suggested_document_no' => ['string', ['length' => 128, 'notnull' => false]],
            'suggested_document_date' => ['date', ['notnull' => false]],
            'suggested_customer_id' => ['bigint', ['notnull' => false]],
            'suggested_project_id' => ['bigint', ['notnull' => false]],
            'suggested_supplier_id' => ['bigint', ['notnull' => false]],
            'suggestion_confidence' => ['integer', ['default' => 0, 'notnull' => true]],
            'analyzed_at' => ['datetime', ['notnull' => false]],
            'duplicate_of' => ['bigint', ['notnull' => false]],
        ];

        foreach ($columns as $name => [$type, $definition]) {
            if (!$table->hasColumn($name)) {
                $table->addColumn($name, $type, $definition);
            }
        }

        if (!$table->hasIndex('re_erp_docs_suggestion')) {
            $table->addIndex(['status', 'suggested_type', 'suggestion_confidence'], 're_erp_docs_suggestion');
        }
        if (!$table->hasIndex('re_erp_docs_duplicate')) {
            $table->addIndex(['duplicate_of'], 're_erp_docs_duplicate');
        }

        return $schema;
    }
}
