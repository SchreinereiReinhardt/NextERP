<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version001900Date20260802150000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();
        if (!$schema->hasTable('re_erp_activities')) {
            $table = $schema->createTable('re_erp_activities');
            $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('customer_id', 'bigint', ['notnull' => false]);
            $table->addColumn('project_id', 'bigint', ['notnull' => false]);
            $table->addColumn('entity_type', 'string', ['length' => 32, 'notnull' => true]);
            $table->addColumn('entity_id', 'bigint', ['notnull' => false]);
            $table->addColumn('action', 'string', ['length' => 48, 'notnull' => true]);
            $table->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('details', 'text', ['notnull' => false]);
            $table->addColumn('created_by', 'string', ['length' => 64, 'notnull' => true]);
            $table->addColumn('created_at', 'datetime', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['project_id', 'created_at'], 're_erp_activity_project');
            $table->addIndex(['customer_id', 'created_at'], 're_erp_activity_customer');
            $table->addIndex(['entity_type', 'entity_id'], 're_erp_activity_entity');
        }
        return $schema;
    }
}
