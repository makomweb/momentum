<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSession2
{
    private function __construct(
        public PaceZone|HeartRateZone $zone,
        public \DateTimeImmutable $start,
        public ?\DateTimeImmutable $end = null,
    ) {
    }

    public static function open(PaceZoneEntry|HeartRateZoneEntry $entry): self
    {
        return new self($entry->zone, $entry->entered);
    }

    public function close(\DateTimeImmutable $end): self
    {
        return new self($this->zone, $this->start, $end);
    }

    public function getSeconds(): int
    {
        assert($this->end instanceof \DateTimeImmutable, 'You must close this session first!');

        return $this->end->getTimestamp() - $this->start->getTimestamp();
    }

    public function getPercentage(int $total): float
    {
        return $total > 0 ? ($this->getSeconds() / $total) * 100 : 0.0;
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
