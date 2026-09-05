<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version024008Date20260904170000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_team_event_users')) {
            $table = $schema->createTable('re_erp_team_event_users');
            $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('event_id', 'bigint', ['notnull' => true]);
            $table->addColumn('user_id', 'string', ['length' => 64, 'notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['event_id', 'user_id'], 're_erp_event_user_unique');
            $table->addIndex(['user_id', 'event_id'], 're_erp_event_user_lookup');
        }
        return $schema;
    }
}
