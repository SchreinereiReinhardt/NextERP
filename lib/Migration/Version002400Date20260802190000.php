<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version002400Date20260802190000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if (!$schema->hasTable('re_erp_customer_contacts')) {
            $table = $schema->createTable('re_erp_customer_contacts');
            $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('customer_id', 'bigint', ['notnull' => true]);
            $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('position', 'string', ['length' => 120, 'notnull' => false]);
            $table->addColumn('phone', 'string', ['length' => 80, 'notnull' => false]);
            $table->addColumn('mobile', 'string', ['length' => 80, 'notnull' => false]);
            $table->addColumn('email', 'string', ['length' => 255, 'notnull' => false]);
            $table->addColumn('notes', 'text', ['notnull' => false]);
            $table->addColumn('is_primary', 'boolean', ['default' => false, 'notnull' => true]);
            $table->addColumn('created_by', 'string', ['length' => 64, 'notnull' => true]);
            $table->addColumn('created_at', 'datetime', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['customer_id', 'is_primary'], 're_erp_contact_customer');
        }

        if (!$schema->hasTable('re_erp_customer_reminders')) {
            $table = $schema->createTable('re_erp_customer_reminders');
            $table->addColumn('id', 'bigint', ['autoincrement' => true, 'notnull' => true]);
            $table->addColumn('customer_id', 'bigint', ['notnull' => true]);
            $table->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
            $table->addColumn('due_date', 'date', ['notnull' => true]);
            $table->addColumn('notes', 'text', ['notnull' => false]);
            $table->addColumn('is_done', 'boolean', ['default' => false, 'notnull' => true]);
            $table->addColumn('created_by', 'string', ['length' => 64, 'notnull' => true]);
            $table->addColumn('created_at', 'datetime', ['notnull' => true]);
            $table->addColumn('completed_at', 'datetime', ['notnull' => false]);
            $table->setPrimaryKey(['id']);
            $table->addIndex(['customer_id', 'is_done', 'due_date'], 're_erp_reminder_customer_due');
        }

        return $schema;
    }
}
