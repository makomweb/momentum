<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSession
{
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public PaceZone|HeartRateZone $zone,
        public int $durationSeconds,
    ) {
    }

    public function getDurationPercentage(int $totalActivitySeconds): float
    {
        return $totalActivitySeconds > 0 ? ($this->durationSeconds / $totalActivitySeconds) * 100 : 0.0;
    }

    public function isInZone(PaceZone|HeartRateZone $zone): bool
    {
        return match (true) {
            $this->zone instanceof PaceZone && $zone instanceof PaceZone => $this->zone === $zone,
            $this->zone instanceof HeartRateZone && $zone instanceof HeartRateZone => $this->zone === $zone,
            default => false,
        };
    }
}
