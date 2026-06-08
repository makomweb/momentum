<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneEntry extends ZoneEntry
{
    public static function fromDataPoint(ActivityDataPoint $point, HeartRate $maxHeartRate): self
    {
        return new self(
            $point->getHeartRateZone($maxHeartRate),
            $point->getTimestamp()
        );
    }
}
