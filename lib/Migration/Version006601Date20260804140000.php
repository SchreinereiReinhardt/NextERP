<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version006601Date20260804140000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if ($schema->hasTable('re_erp_reports')) {
            $table = $schema->getTable('re_erp_reports');
            if (!$table->hasColumn('technician_signature_data')) {
                $table->addColumn('technician_signature_data', 'text', ['notnull' => false]);
            }
            if (!$table->hasColumn('technician_signature_mime')) {
                $table->addColumn('technician_signature_mime', 'string', ['length' => 64, 'notnull' => false]);
            }
            if (!$table->hasColumn('technician_signed_by')) {
                $table->addColumn('technician_signed_by', 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$table->hasColumn('technician_signed_at')) {
                $table->addColumn('technician_signed_at', 'datetime', ['notnull' => false]);
            }
        }
        return $schema;
    }
}
