<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\BackgroundJob;
use OCA\ReinhardtERP\Service\LicenseService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
final class LicenseCheckJob extends TimedJob{
 public function __construct(ITimeFactory $time,private LicenseService $licenses){parent::__construct($time);$this->setInterval(86400);$this->setTimeSensitivity(self::TIME_INSENSITIVE);}
 protected function run(mixed $argument):void{$this->licenses->scheduledCheck();}
}
