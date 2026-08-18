<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Dashboard;
use OCP\Dashboard\Model\WidgetItems;
final class TodayWidget extends AbstractNextErpWidget {
    public function getId(): string { return 'nexterp-today'; }
    public function getTitle(): string { return 'NextERP – Heute'; }
    public function getOrder(): int { return 20; }
    public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
        $items = $this->service->today($userId, $limit);
        return new WidgetItems($items, $items === [] ? 'Heute sind keine NextERP-Einträge vorhanden.' : '');
    }
}
