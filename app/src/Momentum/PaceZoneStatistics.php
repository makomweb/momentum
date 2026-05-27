<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneStatistics
{
    /** @var array<string, array{zone: PaceZone, duration_seconds: int, duration_percentage: float}> */
    private array $stats;

    /**
     * @param array<string, array{zone: PaceZone, duration_seconds: int, duration_percentage: float}> $stats
     * @param ZoneSession[]                                                                           $sessions
     */
    private function __construct(
        array $stats,
        public int $totalSeconds,
        private array $sessions,
    ) {
        $this->stats = $stats;
    }

    /** @param ZoneSession[] $sessions */
    public static function fromSessions(array $sessions, int $totalSeconds): self
    {
        $stats = [];

        foreach (PaceZone::cases() as $zone) {
            $timeInZone = 0;
            foreach ($sessions as $session) {
                if ($session->isInZone($zone)) {
                    $timeInZone += $session->durationSeconds;
                }
            }
            $stats[$zone->value] = [
                'zone' => $zone,
                'duration_seconds' => $timeInZone,
                'duration_percentage' => $totalSeconds > 0 ? ($timeInZone / $totalSeconds) * 100 : 0.0,
            ];
        }

        return new self($stats, $totalSeconds, $sessions);
    }

    public function getTimeInZone(PaceZone $zone): int
    {
        return $this->stats[$zone->value]['duration_seconds'] ?? 0;
    }

    public function getPercentageInZone(PaceZone $zone): float
    {
        return $this->stats[$zone->value]['duration_percentage'] ?? 0.0;
    }

    /**
     * @return array<string, array{zone: PaceZone, duration_seconds: int, duration_percentage: float}>
     */
    public function all(): array
    {
        return $this->stats;
    }

    /**
     * @return ZoneSession[]
     */
    public function getZoneSessions(): array
    {
        return $this->sessions;
    }
}
