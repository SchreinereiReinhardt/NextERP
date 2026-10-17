<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version030001Date20261017101500 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('re_erp_suppliers')) {
            $table = $schema->getTable('re_erp_suppliers');
            if (!$table->hasColumn('datev_creditor_account')) {
                $table->addColumn('datev_creditor_account', 'string', [
                    'length' => 16,
                    'notnull' => false,
                ]);
            }
        }

        if ($schema->hasTable('re_erp_documents')) {
            $table = $schema->getTable('re_erp_documents');
            if (!$table->hasColumn('datev_expense_account')) {
                $table->addColumn('datev_expense_account', 'string', [
                    'length' => 16,
                    'notnull' => false,
                ]);
            }
            if (!$table->hasColumn('datev_vat_rate')) {
                $table->addColumn('datev_vat_rate', 'decimal', [
                    'precision' => 5,
                    'scale' => 2,
                    'notnull' => false,
                ]);
            }
        }

        return $schema;
    }
}
