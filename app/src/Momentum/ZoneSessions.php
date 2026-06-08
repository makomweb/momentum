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
        return new self($endTime, new ZoneEntries($athlete->getTreshold(), $athlete->getMaxHeartRate(), null, $dataPoints));
    }

    /** @param ActivityDataPoint[] $dataPoints */
    public static function createWithSpeedZones(AthleteInterface $athlete, \DateTimeImmutable $endTime, array $dataPoints, Velocity $averageVelocity): self
    {
        return new self($endTime, new ZoneEntries($athlete->getTreshold(), $athlete->getMaxHeartRate(), $averageVelocity, $dataPoints));
    }

    /** @return ZoneSession[] */
    public function getPaceZoneSessions(): array
    {
        $entries = $this->zoneEntries->getPaceZoneEntries();

        /** @var ZoneSession[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        return self::finalize($result, $this->endTime);
    }

    /** @return ZoneSession[] */
    public function getHeartRateSessions(): array
    {
        $entries = $this->zoneEntries->getHeartRateZoneEntries();

        /** @var ZoneSession[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        return self::finalize($result, $this->endTime);
    }

    /** @return ZoneSession[] */
    public function getSpeedZoneSessions(): array
    {
        $entries = $this->zoneEntries->getSpeedZoneEntries();

        /** @var ZoneSession[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        return self::finalize($result, $this->endTime);
    }

    /**
     * @param ZoneSession[] $carry
     *
     * @return ZoneSession[]
     */
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry|SpeedZoneEntry $entry): array
    {
        if (!empty($carry)) {
            $previous = array_pop($carry);
            $carry[] = $previous->close($entry->entered);
        }

        return [...$carry, ZoneSession::open($entry)];
    }

    /**
     * @param ZoneSession[] $sessions
     *
     * @return ZoneSession[]
     */
    private static function finalize(array $sessions, \DateTimeImmutable $endTime): array
    {
        if (empty($sessions)) {
            return [];
        }

        $last = array_key_last($sessions);
        $sessions[$last] = $sessions[$last]->close($endTime);

        return $sessions;
    }
}
