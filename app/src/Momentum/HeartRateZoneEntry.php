<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneEntry implements \Stringable
{
    private function __construct(
        public HeartRateZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }

    public static function fromDataPoint(ActivityDataPoint $point, HeartRate $maxHeartRate): self
    {
        return new self(
            $point->getHeartRateZone($maxHeartRate),
            $point->getTimestamp()
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
