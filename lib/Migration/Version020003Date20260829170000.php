<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version020003Date20260829170000 extends SimpleMigrationStep {
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();
		if ($schema->hasTable('re_erp_invoices')) {
			$table = $schema->getTable('re_erp_invoices');
			$columns = [
				'invoice_type' => ['string', ['notnull'=>true, 'default'=>'invoice', 'length'=>32]],
				'related_invoice_id' => ['bigint', ['notnull'=>false]],
				'advance_net_amount' => ['decimal', ['notnull'=>true, 'default'=>0, 'precision'=>15, 'scale'=>2]],
				'advance_gross_amount' => ['decimal', ['notnull'=>true, 'default'=>0, 'precision'=>15, 'scale'=>2]],
			];
			foreach ($columns as $name => [$type,$opts]) {
				if (!$table->hasColumn($name)) $table->addColumn($name,$type,$opts);
			}
			if (!$table->hasIndex('re_erp_invoice_type')) $table->addIndex(['invoice_type'],'re_erp_invoice_type');
			if (!$table->hasIndex('re_erp_invoice_related')) $table->addIndex(['related_invoice_id'],'re_erp_invoice_related');
		}
		return $schema;
	}
}
