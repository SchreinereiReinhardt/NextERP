<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version002600Date20260802210000 extends SimpleMigrationStep {
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
        $schema = $schemaClosure();

        if ($schema->hasTable('re_erp_customers')) {
            $table = $schema->getTable('re_erp_customers');
            if (!$table->hasColumn('nc_addressbook_key')) {
                $table->addColumn('nc_addressbook_key', 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$table->hasColumn('nc_contact_id')) {
                $table->addColumn('nc_contact_id', 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$table->hasColumn('nc_contact_uid')) {
                $table->addColumn('nc_contact_uid', 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$table->hasColumn('nc_contact_label')) {
                $table->addColumn('nc_contact_label', 'string', ['length' => 255, 'notnull' => false]);
            }
            if (!$table->hasColumn('nc_contact_synced_at')) {
                $table->addColumn('nc_contact_synced_at', 'datetime', ['notnull' => false]);
            }
            if (!$table->hasIndex('re_erp_customer_nc_contact')) {
                $table->addIndex(['nc_addressbook_key', 'nc_contact_id'], 're_erp_customer_nc_contact');
            }
        }

        return $schema;
    }
}
