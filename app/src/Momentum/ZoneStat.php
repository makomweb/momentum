<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneStat
{
    public function __construct(
        public PaceZone|HeartRateZone $zone,
        public int $durationSeconds,
        public float $durationPercentage,
    ) {
    }

    public function getDurationPercentage(): float
    {
        return $this->durationPercentage;
    }
}
