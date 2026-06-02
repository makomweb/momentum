<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneEntry implements \Stringable
{
    private function __construct(
        public PaceZone $zone,
        public \DateTimeImmutable $entered,
    ) {
    }

    public static function fromDataPoint(ActivityDataPoint $point, Pace $treshold): self
    {
        return new self(
            $point->getPaceZone($treshold),
            $point->getTimestamp()
        );
    }

    public function isEasy(): bool
    {
        return in_array($this->zone, [PaceZone::ZONE_1, PaceZone::ZONE_2]);
    }

    public function isHard(): bool
    {
        return in_array($this->zone, [PaceZone::ZONE_4, PaceZone::ZONE_5]);
    }

    public function isEqual(PaceZone $zone): bool
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
