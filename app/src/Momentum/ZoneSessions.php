<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSessions
{
    private function __construct(private \DateTimeImmutable $endTime, private ZoneEntries $zoneEntries)
    {
    }

    /** @param ActivityDataPoint[] $dataPoints */
    public static function create(AthleteInterface $athlete, \DateTimeImmutable $endTime, array $dataPoints): self
    {
        return new self($endTime, new ZoneEntries($athlete->getTreshold(), $athlete->getMaxHeartRate(), $dataPoints));
    }

    /** @return ZoneSession2[] */
    public function getPaceZoneSessions(): array
    {
        $entries = $this->zoneEntries->getPaceZoneEntries();

        /** @var ZoneSession2[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        if (!empty($result)) {
            $last = array_key_last($result);
            $result[$last] = $result[$last]->close($this->endTime);
        }

        return $result;
    }

    /** @return ZoneSession2[] */
    public function getHeartRateSessions(): array
    {
        $entries = $this->zoneEntries->getHeartRateZoneEntries();

        /** @var ZoneSession2[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        if (!empty($result)) {
            $last = array_key_last($result);
            $result[$last] = $result[$last]->close($this->endTime);
        }

        return $result;
    }

    /**
     * @param ZoneSession2[] $carry
     *
     * @return ZoneSession2[]
     */
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $entry): array
    {
        if (!empty($carry)) {
            $previous = array_pop($carry);
            $carry[] = $previous->close($entry->entered);
        }

        return [...$carry, ZoneSession2::open($entry)];
    }
}
