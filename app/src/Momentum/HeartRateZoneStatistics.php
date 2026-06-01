<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRateZoneStatistics
{
    /** @param array<string, int> $statistics */
    private function __construct(public array $statistics = [])
    {
    }

    /**
     * @param ZoneSession[]   $sessions
     * @param HeartRateZone[] $zones
     */
    public static function fromSessions(array $sessions, array $zones): self
    {
        /** @var array<string, int> $empty */
        $empty = array_reduce(
            $zones,
            /**
             * @param array<string, int> $carry
             *
             * @return array<string, int>
             */
            static function (array $carry, HeartRateZone $zone): array {
                $carry[$zone->value] = 0;

                return $carry;
            },
            []
        );

        /** @var array<string, int> $statistics */
        $statistics = array_reduce(
            $sessions,
            /**
             * @param array<string, int> $carry
             *
             * @return array<string, int>
             */
            static function (array $carry, ZoneSession $session): array {
                // dump(['carry' => $carry, 'session' => $session]);

                dump($session->zone->value.': '.implode(', ', array_keys($carry)));

                /** @var int $value */
                $value = $carry[$session->zone->value];
                $carry[$session->zone->value] = $value + $session->getSeconds();

                return $carry;
            },
            $empty
        );

        return new self($statistics);
    }

    /**
     * @return ZoneStat[]
     */
    public function toArray(int $total): array
    {
        return iterator_to_array($this->yieldStatistics($total));
    }

    /**
     * @return \Generator<ZoneStat>
     */
    private function yieldStatistics(int $total): \Generator
    {
        foreach ($this->statistics as $zoneValue => $duration) {
            yield new ZoneStat(
                HeartRateZone::from($zoneValue),
                $duration,
                round($total > 0 ? ($duration / $total) * 100 : 0.0, 2),
            );
        }
    }
}
