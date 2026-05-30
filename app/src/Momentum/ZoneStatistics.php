<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneStatistics
{
    /** @param array<string, ZoneDuration> $statistics */
    private function __construct(public array $statistics = [], public int $totalSeconds = 0)
    {
    }

    /**
     * @param ZoneSession[]                 $sessions
     * @param array<PaceZone|HeartRateZone> $zones
     */
    public static function fromSessions(array $sessions, int $totalSeconds, array $zones): self
    {
        /** @var array<string, ZoneDuration> $statistics */
        $statistics = array_map(
            static fn (PaceZone|HeartRateZone $zone) => array_reduce(
                $sessions,
                static fn (ZoneDuration $duration, ZoneSession $session) => $duration->add($session),
                ZoneDuration::initial($zone, $totalSeconds)
            ),
            $zones
        );

        return new self($statistics, $totalSeconds);
    }

    public function getDurationInZone(PaceZone|HeartRateZone $zone): int
    {
        return $this->getDuration($zone)->seconds;
    }

    public function getPercentageInZone(PaceZone|HeartRateZone $zone): float
    {
        return $this->getDuration($zone)->getDurationPercentage();
    }

    private function getDuration(PaceZone|HeartRateZone $zone): ZoneDuration
    {
        return $this->statistics[$zone->value];
    }
}
