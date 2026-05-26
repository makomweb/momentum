<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneStatistics
{
    /**
     * @param array<string, array{
     *     zone: PaceZone,
     *     duration_seconds: int,
     *     duration_percentage: float,
     * }> $zoneStats
     * @param ZoneSession[] $zoneSessions
     */
    public function __construct(
        public array $zoneStats,
        public int $totalSeconds,
        public array $zoneSessions = [],
    ) {
    }

    public function getTimeInZone(PaceZone $zone): int
    {
        return $this->zoneStats[$zone->value]['duration_seconds'] ?? 0;
    }

    public function getPercentageInZone(PaceZone $zone): float
    {
        return $this->zoneStats[$zone->value]['duration_percentage'] ?? 0.0;
    }

    public function getZonePercentage(PaceZone $zone): float
    {
        return $this->getPercentageInZone($zone);
    }

    /**
     * @return ZoneSession[]
     */
    public function getZoneSessions(): array
    {
        return $this->zoneSessions;
    }
}
