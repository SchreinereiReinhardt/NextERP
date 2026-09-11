<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020104Date20260829193000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema=$schemaClosure();
		foreach (['re_erp_offer_items','re_erp_invoice_items'] as $tableName) {
			if ($schema->hasTable($tableName)) {
				$table=$schema->getTable($tableName);
				if (!$table->hasColumn('is_alternative')) {
					$table->addColumn('is_alternative','boolean',['notnull'=>true,'default'=>0]);
				}
			}
		}
		return $schema;
	}
}
