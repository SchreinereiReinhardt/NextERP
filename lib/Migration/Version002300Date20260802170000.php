<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version002300Date20260802170000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        $indexes = [
            're_erp_projects' => [
                ['columns' => ['customer_id', 'archived'], 'name' => 're_erp_project_customer_active'],
                ['columns' => ['status'], 'name' => 're_erp_project_status'],
            ],
            're_erp_reports' => [
                ['columns' => ['project_id', 'report_date'], 'name' => 're_erp_report_project_date'],
                ['columns' => ['customer_id', 'report_date'], 'name' => 're_erp_report_customer_date'],
            ],
            're_erp_workday_entries' => [
                ['columns' => ['project_id', 'imported_to_report_id'], 'name' => 're_erp_time_project_import'],
                ['columns' => ['billing_status'], 'name' => 're_erp_time_billing_status'],
            ],
            're_erp_report_hours' => [
                ['columns' => ['report_id'], 'name' => 're_erp_report_hours_report'],
            ],
            're_erp_report_items' => [
                ['columns' => ['report_id'], 'name' => 're_erp_report_items_report'],
            ],
        ];
        foreach ($indexes as $tableName => $definitions) {
            if (!$schema->hasTable($tableName)) {
                continue;
            }
            $table = $schema->getTable($tableName);
            foreach ($definitions as $definition) {
                if (!$table->hasIndex($definition['name'])) {
                    $table->addIndex($definition['columns'], $definition['name']);
                }
            }
        }
        return $schema;
    }
}
