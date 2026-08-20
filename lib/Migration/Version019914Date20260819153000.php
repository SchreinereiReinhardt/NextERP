<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

final class Version019914Date20260819153000 extends SimpleMigrationStep {
	public function changeSchema(
		IOutput $output,
		Closure $schemaClosure,
		array $options
	): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('re_erp_order_notes')) {
			$table = $schema->createTable('re_erp_order_notes');

			$table->addColumn('id', 'bigint', [
				'autoincrement' => true,
				'notnull' => true,
			]);

			$table->addColumn('order_id', 'bigint', [
				'notnull' => false,
			]);

			$table->addColumn('project_id', 'bigint', [
				'notnull' => false,
			]);

			$table->addColumn('note_type', 'string', [
				'length' => 32,
				'notnull' => true,
				'default' => 'note',
			]);

			$table->addColumn('content', 'text', [
				'notnull' => true,
			]);

			$table->addColumn('created_by', 'string', [
				'length' => 64,
				'notnull' => true,
			]);

			$table->addColumn('created_at', 'datetime', [
				'notnull' => true,
			]);

			$table->addColumn('updated_at', 'datetime', [
				'notnull' => false,
			]);

			$table->setPrimaryKey(['id']);

			$table->addIndex(
				['order_id', 'created_at'],
				're_erp_note_order'
			);

			$table->addIndex(
				['project_id', 'created_at'],
				're_erp_note_project'
			);

			$table->addIndex(
				['note_type'],
				're_erp_note_type'
			);
		}

		return $schema;
	}
}
