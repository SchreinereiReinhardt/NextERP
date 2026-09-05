<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version024007Date20260904140000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_team_events')) {
            return $schema;
        }
        $table = $schema->getTable('re_erp_team_events');
        if (!$table->hasColumn('customer_id')) {
            $table->addColumn('customer_id', 'bigint', ['notnull' => false]);
        }
        if (!$table->hasColumn('project_id')) {
            $table->addColumn('project_id', 'bigint', ['notnull' => false]);
        }
        if (!$table->hasColumn('assigned_user_id')) {
            $table->addColumn('assigned_user_id', 'string', ['length' => 64, 'notnull' => false]);
        }
        if (!$table->hasIndex('re_erp_event_project_start')) {
            $table->addIndex(['project_id', 'start_at'], 're_erp_event_project_start');
        }
        if (!$table->hasIndex('re_erp_event_customer_start')) {
            $table->addIndex(['customer_id', 'start_at'], 're_erp_event_customer_start');
        }
        if (!$table->hasIndex('re_erp_event_user_start')) {
            $table->addIndex(['assigned_user_id', 'start_at'], 're_erp_event_user_start');
        }
        return $schema;
    }
}
