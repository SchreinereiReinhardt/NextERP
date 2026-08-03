<?php
declare(strict_types=1);

namespace OCA\ReinhardtERP\BackgroundJob;

use OCA\ReinhardtERP\Service\DocumentBackgroundScanService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

final class DocumentInboxScanJob extends TimedJob {
    public function __construct(
        ITimeFactory $time,
        private DocumentBackgroundScanService $scanner,
    ) {
        parent::__construct($time);
        $this->setInterval(300);
        $this->setTimeSensitivity(self::TIME_INSENSITIVE);
    }

    protected function run(mixed $argument): void {
        $this->scanner->scan();
    }
}
