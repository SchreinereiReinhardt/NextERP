<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Dashboard;
use OCP\Dashboard\Model\WidgetItems;
use OCP\Dashboard\Model\WidgetButton;
final class ProjectsWidget extends AbstractNextErpWidget {
    public function getId(): string { return 'nexterp-projects'; }
    public function getTitle(): string { return 'NextERP – Projekte'; }
    public function getOrder(): int { return 22; }
    public function getUrl(): ?string { return $this->url->linkToRouteAbsolute('reinhardterp.page.projects'); }
    public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
        $items = $this->service->projects($userId, $limit);
        return new WidgetItems($items, $items === [] ? 'Keine freigegebenen aktiven Projekte vorhanden.' : '');
    }
    public function getWidgetButtons(string $userId): array {
        return [new WidgetButton(WidgetButton::TYPE_MORE, $this->getUrl() ?? '', 'Alle Projekte')];
    }
}
