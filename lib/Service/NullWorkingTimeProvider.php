<?php
declare(strict_types=1);
namespace OCA\ReinhardtERP\Service;

/** Default provider when no HR/attendance integration is configured. */
final class NullWorkingTimeProvider implements WorkingTimeProviderInterface {
    public function id(): string { return 'none'; }
    public function label(): string { return 'Keine externe HR-Stempeluhr'; }
    public function isAvailable(): bool { return false; }
    public function attendance(string $userId, string $date): array {
        return [
            'available' => false,
            'hours' => null,
            'source' => $this->label(),
            'note' => 'Betrio erfasst weiterhin nur Projektstunden. Anwesenheit, Pausen, Urlaub und Krankheit bleiben Aufgabe einer angebundenen HR-App.',
        ];
    }
}
