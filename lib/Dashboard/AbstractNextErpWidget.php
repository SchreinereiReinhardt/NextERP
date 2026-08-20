<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\Dashboard;

use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\IURLGenerator;
use OCA\ReinhardtERP\Service\DashboardWidgetService;

abstract class AbstractNextErpWidget implements IAPIWidgetV2, IButtonWidget, IIconWidget {
    public function __construct(
        protected DashboardWidgetService $service,
        protected IURLGenerator $url,
    ) {}

    public function getOrder(): int { return 20; }
    public function getIconClass(): string { return ''; }
    public function getIconUrl(): string {
        return $this->url->getAbsoluteURL($this->url->imagePath('reinhardterp', 'app.svg'));
    }
    public function getUrl(): ?string {
        return $this->url->linkToRouteAbsolute('reinhardterp.page.index');
    }
    public function load(): void {}
    public function getWidgetButtons(string $userId): array {
        return [new WidgetButton(WidgetButton::TYPE_MORE, $this->getUrl() ?? '', 'Betrio öffnen')];
    }
}
