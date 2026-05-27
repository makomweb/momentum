<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSession
{
    public function __construct(
        public \DateTimeImmutable $startTime,
        public \DateTimeImmutable $endTime,
        public PaceZone|HeartRateZone $zone,
        public int $durationSeconds,
    ) {
    }

    public function getDurationPercentage(int $totalActivitySeconds): float
    {
        return $totalActivitySeconds > 0 ? ($this->durationSeconds / $totalActivitySeconds) * 100 : 0.0;
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

    public function isInZone(PaceZone|HeartRateZone $zone): bool
    {
        return $this->zone === $zone;
    }
}
