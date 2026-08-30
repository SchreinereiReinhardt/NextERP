<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020002Date20260829150000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('re_erp_invoices')) {
			$table = $schema->getTable('re_erp_invoices');

			$columns = [
				'order_id' => ['bigint', ['notnull' => false]],
				'service_date' => ['date', ['notnull' => false]],
				'due_date' => ['date', ['notnull' => false]],
				'notes' => ['text', ['notnull' => false]],
				'company_snapshot' => ['text', ['notnull' => false]],
				'customer_snapshot' => ['text', ['notnull' => false]],
				'finalized_at' => ['datetime', ['notnull' => false]],
				'paid_at' => ['datetime', ['notnull' => false]],
				'cancelled_at' => ['datetime', ['notnull' => false]],
				'updated_at' => ['datetime', ['notnull' => false]],
			];

			foreach ($columns as $name => [$type, $options]) {
				if (!$table->hasColumn($name)) {
					$table->addColumn($name, $type, $options);
				}
			}

			if (!$table->hasIndex('re_erp_invoice_order')) {
				$table->addIndex(['order_id'], 're_erp_invoice_order');
			}
			if (!$table->hasIndex('re_erp_invoice_status_date')) {
				$table->addIndex(['status', 'invoice_date'], 're_erp_invoice_status_date');
			}
		}

		return $schema;
	}
}
