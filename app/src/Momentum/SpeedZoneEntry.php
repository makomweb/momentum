<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class SpeedZoneEntry implements \Stringable
{
    private function __construct(
        public SpeedZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }

    public static function fromDataPoint(ActivityDataPoint $point, Velocity $averageVelocity): self
    {
        return new self(
            $point->getSpeedZone($averageVelocity),
            $point->getTimestamp()
        );
    }

    public function isEqual(SpeedZone $zone): bool
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
