<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneEntry implements \Stringable
{
    private function __construct(
        public \DateTimeImmutable $entered,
        public HeartRateZone $zone,
    ) {
    }

    public static function fromDataPoint(ActivityDataPoint $point, HeartRate $maxHeartRate): self
    {
        return new self(
            $point->getTimestamp(),
            $point->getHeartRateZone($maxHeartRate)
        );
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
