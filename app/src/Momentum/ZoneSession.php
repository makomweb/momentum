<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSession
{
    public function __construct(
        public PaceZone|HeartRateZone $zone,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
    ) {
    }

    public function getSeconds(): int
    {
        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    public function getPercentage(int $total): float
    {
        return $total > 0 ? ($this->getSeconds() / $total) * 100 : 0.0;
    }

    /**
     * @param ZoneSession[] $sessions
     */
    public function isConfirmedByOneOf(array $sessions): bool
    {
        assert($this->zone instanceof PaceZone, 'Should be a pace zone!');

        // Find all HR zones that overlap with this pace session
        $hrZones = new ExtractHeartRateZones($sessions)->for($this);

        foreach ($hrZones as $hrZone) {
            if ($hrZone->confirms($this->zone)) {
                return true;
            }
        }

        return false;
    }

    public function isEasy(): bool
    {
        assert($this->zone instanceof PaceZone, 'Should be a pace zone!');

        return in_array($this->zone, [PaceZone::ZONE_1, PaceZone::ZONE_2]);
    }

    public function isHard(): bool
    {
        assert($this->zone instanceof PaceZone, 'Should be a pace zone!');

        return in_array($this->zone, [PaceZone::ZONE_4, PaceZone::ZONE_5]);
    }

    public function isEqual(PaceZone|HeartRateZone $zone): bool
    {
        return $this->zone === $zone;
    }
}
