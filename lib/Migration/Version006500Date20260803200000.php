<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version006500Date20260803200000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('re_erp_documents')) {
            $table = $schema->getTable('re_erp_documents');
            if (!$table->hasColumn('offer_id')) {
                $table->addColumn('offer_id', 'bigint', ['notnull' => false]);
            }
            if (!$table->hasColumn('order_id')) {
                $table->addColumn('order_id', 'bigint', ['notnull' => false]);
            }
            if (!$table->hasColumn('imported_at')) {
                $table->addColumn('imported_at', 'datetime', ['notnull' => false]);
            }
            if (!$table->hasIndex('re_erp_doc_offer')) {
                $table->addIndex(['offer_id'], 're_erp_doc_offer');
            }
        }
        if ($schema->hasTable('re_erp_offers')) {
            $table = $schema->getTable('re_erp_offers');
            if (!$table->hasColumn('source_document_id')) {
                $table->addColumn('source_document_id', 'bigint', ['notnull' => false]);
            }
            if (!$table->hasIndex('re_erp_offer_source_doc')) {
                $table->addIndex(['source_document_id'], 're_erp_offer_source_doc');
            }
        }
        return $schema;
    }
}
