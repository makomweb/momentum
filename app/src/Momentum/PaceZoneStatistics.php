<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneStatistics
{
    /** @param array<string, PaceZoneDuration> $statistics */
    private function __construct(public array $statistics = [], public int $totalSeconds = 0)
    {
    }

    /**
     * @param ZoneSession[] $sessions
     */
    public static function fromSessions(array $sessions, int $totalSeconds): self
    {
        /** @var array<string, PaceZoneDuration> $statistics */
        $statistics = array_map(
            static fn (PaceZone $zone) => array_reduce(
                $sessions,
                static fn (PaceZoneDuration $carry, ZoneSession $session) => $carry->add($session),
                new PaceZoneDuration($zone, 0, $totalSeconds)
            ),
            PaceZone::cases()
        );

        return new self($statistics, $totalSeconds);
    }

    public function getDurationInZone(PaceZone $zone): int
    {
        return $this->getDuration($zone)->seconds;
    }

    public function getPercentageInZone(PaceZone $zone): float
    {
        return $this->getDuration($zone)->getDurationPercentage();
    }

    private function getDuration(PaceZone $zone): PaceZoneDuration
    {
        return $this->statistics[$zone->value];
    }
}
