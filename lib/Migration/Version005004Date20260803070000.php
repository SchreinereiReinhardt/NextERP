<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version005004Date20260803070000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_customers')) {
            return $schema;
        }
        $table = $schema->getTable('re_erp_customers');
        if (!$table->hasColumn('mobile')) {
            $table->addColumn('mobile', 'string', ['length' => 100, 'notnull' => false]);
        }
        return $schema;
    }
}
