<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneStatistics
{
    /** @param array<string, HeartRateZoneDuration> $statistics */
    private function __construct(public array $statistics = [], public int $totalSeconds = 0)
    {
    }

    /**
     * @param ZoneSession[] $sessions
     */
    public static function fromSessions(array $sessions, int $totalSeconds): self
    {
        /** @var array<string, HeartRateZoneDuration> $statistics */
        $statistics = array_map(
            static fn (HeartRateZone $zone) => array_reduce(
                $sessions,
                static fn (HeartRateZoneDuration $carry, ZoneSession $session) => $carry->add($session),
                new HeartRateZoneDuration($zone, 0, $totalSeconds)
            ),
            HeartRateZone::cases()
        );

        return new self($statistics, $totalSeconds);
    }

    public function getDurationInZone(HeartRateZone $zone): int
    {
        return $this->getDuration($zone)->seconds;
    }

    public function getPercentageInZone(HeartRateZone $zone): float
    {
        return $this->getDuration($zone)->getDurationPercentage();
    }

    private function getDuration(HeartRateZone $zone): HeartRateZoneDuration
    {
        return $this->statistics[$zone->value];
    }
}
