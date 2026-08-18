<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\AppInfo;

use OCP\Util;
use OCA\ReinhardtERP\BackgroundJob\DocumentInboxScanJob;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\BackgroundJob\IJobList;
use OCA\ReinhardtERP\Dashboard\TodayWidget;
use OCA\ReinhardtERP\Dashboard\AttentionWidget;
use OCA\ReinhardtERP\Dashboard\ProjectsWidget;

final class Application extends App implements IBootstrap {
    public const APP_ID = 'reinhardterp';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerDashboardWidget(TodayWidget::class);
        $context->registerDashboardWidget(AttentionWidget::class);
        $context->registerDashboardWidget(ProjectsWidget::class);
    }

    public function boot(IBootContext $context): void {
  Util::addScript('reinhardterp','pwa-guard');
        $context->injectFn(static function (IJobList $jobs): void {
            $jobs->add(DocumentInboxScanJob::class);
        });
    }
}
