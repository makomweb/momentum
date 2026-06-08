<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSession implements \Stringable
{
    // TODO: Make ctor private!
    public function __construct(
        public PaceZone|HeartRateZone|SpeedZone $zone,
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
    ) {
    }

    public static function open(PaceZoneEntry|HeartRateZoneEntry|SpeedZoneEntry $entry): self
    {
        return new self(
            $entry->zone,
            $entry->entered,
            $entry->entered->add(\DateInterval::createFromDateString('1 seconds'))
        );
    }

    public function close(\DateTimeImmutable $end): self
    {
        return new self($this->zone, $this->start,
            $this->start < $end
                ? $end
                : $this->start->add(\DateInterval::createFromDateString('1 seconds'))
        );
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

    public function isEqual(PaceZone|HeartRateZone|SpeedZone $zone): bool
    {
        return $this->zone === $zone;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s · %s · %d second(s)',
            $this->zone->value,
            $this->start->format('D M d, y · H:i:s'),
            $this->getSeconds()
        );
    }
}
