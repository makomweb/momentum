<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneEntry
{
    public function __construct(
        public PaceZone $zone,
        public \DateTimeImmutable $entered,
    ) {
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
}
