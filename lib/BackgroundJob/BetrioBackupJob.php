<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\BackgroundJob;
use OCA\ReinhardtERP\Service\BackupService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
final class BetrioBackupJob extends TimedJob{
 public function __construct(ITimeFactory $time,private BackupService $backups){parent::__construct($time);$this->setInterval(3600);$this->setTimeSensitivity(self::TIME_INSENSITIVE);}
 protected function run(mixed $argument):void{$this->backups->runAutomaticIfDue();}
}
