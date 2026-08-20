<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Dashboard;
use OCP\Dashboard\Model\WidgetItems;
final class AttentionWidget extends AbstractNextErpWidget {
    public function getId(): string { return 'nexterp-attention'; }
    public function getTitle(): string { return 'Betrio – Handlungsbedarf'; }
    public function getOrder(): int { return 21; }
    public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
        $items = $this->service->attention($userId, $limit);
        return new WidgetItems($items, $items === [] ? 'Aktuell gibt es keinen dringenden Handlungsbedarf.' : '');
    }
}
