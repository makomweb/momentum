<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Activity
{
    private function __construct(private \DateTimeImmutable $endTime, private ZoneEntries $zoneEntries)
    {
    }

    /** @param ActivityDataPoint[] $dataPoints */
    public static function create(AthleteInterface $athlete, \DateTimeImmutable $endTime, array $dataPoints): self
    {
        return new self($endTime, ZoneEntries::create($athlete, $dataPoints));
    }

    /** @return Session[] */
    public function getPaceZoneSessions(): array
    {
        $entries = $this->zoneEntries->getPaceZoneEntries();

        /** @var Session[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        return self::finalize($result, $this->endTime);
    }

    /** @return Session[] */
    public function getHeartRateSessions(): array
    {
        $entries = $this->zoneEntries->getHeartRateZoneEntries();

        /** @var Session[] $result */
        $result = array_reduce($entries, self::reduce(...), []);

        return self::finalize($result, $this->endTime);
    }

    /**
     * @param Session[] $carry
     *
     * @return Session[]
     */
    private static function reduce(array $carry, PaceZoneEntry|HeartRateZoneEntry $entry): array
    {
        if (!empty($carry)) {
            $previous = array_pop($carry);
            $carry[] = $previous->close($entry->entered);
        }

        return [...$carry, Session::open($entry)];
    }

    /**
     * @param Session[] $sessions
     *
     * @return Session[]
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
