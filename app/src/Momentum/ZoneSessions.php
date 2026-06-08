<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneSessions
{
    private function __construct(private \DateTimeImmutable $endTime, private ZoneEntries $zoneEntries)
    {
    }

    /**
     * @param ActivityDataPoint[]        $dataPoints
     * @param array{0: int, 1: int}|null $movingTimeWindow [startOffset, endOffset] in seconds from start time, or null to use all points
     */
    public static function create(
        AthleteInterface $athlete,
        \DateTimeImmutable $endTime,
        array $dataPoints,
        ?array $movingTimeWindow = null,
    ): self {
        return new self(
            $endTime,
            new ZoneEntries(
                $athlete->getTreshold(),
                $athlete->getMaxHeartRate(),
                $dataPoints,
                $movingTimeWindow
            )
        );
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

    /**
     * @param ZoneSession[] $carry
     *
     * @return ZoneSession[]
     */
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $entry): array
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
