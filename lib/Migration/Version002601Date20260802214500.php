<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version002601Date20260802214500 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_team_events')) {
            return $schema;
        }
        $table = $schema->getTable('re_erp_team_events');
        if (!$table->hasColumn('calendar_uid')) {
            $table->addColumn('calendar_uid', 'string', ['length' => 255, 'notnull' => false]);
        }
        if (!$table->hasColumn('sync_source')) {
            $table->addColumn('sync_source', 'string', ['length' => 32, 'default' => 'erp', 'notnull' => true]);
        }
        if (!$table->hasColumn('sync_hash')) {
            $table->addColumn('sync_hash', 'string', ['length' => 64, 'notnull' => false]);
        }
        if (!$table->hasColumn('is_deleted')) {
            $table->addColumn('is_deleted', 'boolean', ['default' => false, 'notnull' => true]);
        }
        if (!$table->hasColumn('last_synced_at')) {
            $table->addColumn('last_synced_at', 'datetime', ['notnull' => false]);
        }
        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', ['notnull' => false]);
        }
        if (!$table->hasIndex('re_erp_event_calendar_object')) {
            $table->addIndex(['calendar_uri', 'calendar_object_uri'], 're_erp_event_calendar_object');
        }
        if (!$table->hasIndex('re_erp_event_deleted_start')) {
            $table->addIndex(['is_deleted', 'start_at'], 're_erp_event_deleted_start');
        }
        return $schema;
    }
}
