<?php

declare(strict_types=1);

namespace App\Momentum;

abstract readonly class ZoneEntry implements \Stringable
{
    protected function __construct(
        public HeartRateZone|PaceZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }

    public function isEqual(HeartRateZone|PaceZone $zone): bool
    {
        return $this->zone === $zone;
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
