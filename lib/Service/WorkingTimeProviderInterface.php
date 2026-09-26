<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

/**
 * Adapter contract for external HR/attendance systems.
 * Providers expose attendance only; project allocation remains owned by Betrio.
 */
interface WorkingTimeProviderInterface {
    public function id(): string;
    public function label(): string;
    public function isAvailable(): bool;
    /** @return array{available:bool,hours:?float,source:string,note:string} */
    public function attendance(string $userId, string $date): array;
}
