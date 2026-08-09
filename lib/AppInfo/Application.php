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

final class Application extends App implements IBootstrap {
    public const APP_ID = 'reinhardterp';

    public function __construct(array $urlParams = []) {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void {
    }

    public function boot(IBootContext $context): void {
  Util::addScript('reinhardterp','pwa-guard');
        $context->injectFn(static function (IJobList $jobs): void {
            $jobs->add(DocumentInboxScanJob::class);
        });
    }
}
