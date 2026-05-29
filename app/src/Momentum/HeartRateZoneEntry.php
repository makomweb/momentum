<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneEntry
{
    public function __construct(
        public HeartRateZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }
}
