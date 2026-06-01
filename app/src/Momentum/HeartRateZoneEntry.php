<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneEntry implements \Stringable
{
    public function __construct(
        public HeartRateZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }
    
    public function __toString(): string
    {
        return sprintf(
            '%s · %s',
            $this->entered->format('D M d, y · H:i:s'),
            $this->zone->value
        );
    }
}
