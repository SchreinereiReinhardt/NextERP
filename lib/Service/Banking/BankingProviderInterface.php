<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service\Banking;
interface BankingProviderInterface {
 public function saveConfiguration(array $data): void;
 public function configuration(): array;
 public function startSync(int $days=90): array;
 public function continueAuthentication(?string $tan=null): array;
}
