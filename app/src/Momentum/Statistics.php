<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Statistics
{
    /** @param array<string, array{description: string, value: int}> $statistics */
    private function __construct(public array $statistics = [])
    {
    }

    /**
     * @param Session[]                     $sessions
     * @param array<HeartRateZone|PaceZone> $zones
     */
    public static function fromSessions(AthleteInterface $athlete, array $sessions, array $zones): self
    {
        $descriptions = new ZoneDescription($athlete);

        /** @var array<string, array{description: string, value: int}> $empty */
        $empty = array_reduce(
            $zones,
            /**
             * @param array<string, array{description: string, value: int}> $carry
             *
             * @return array<string, array{description: string, value: int}>
             */
            static function (array $carry, HeartRateZone|PaceZone $zone) use ($descriptions): array {
                $carry[$zone->value] = [
                    'description' => $descriptions->getDescription($zone),
                    'value' => 0,
                ];

                return $carry;
            },
            []
        );

        /** @var array<string, array{description: string, value: int}> $statistics */
        $statistics = array_reduce(
            $sessions,
            /**
             * @param array<string, array{description: string, value: int}> $carry
             *
             * @return array<string, array{description: string, value: int}>
             */
            static function (array $carry, Session $session) use ($descriptions): array {
                /** @var array{description: string, value: int} $zone */
                $zone = $carry[$session->zone->value];
                $carry[$session->zone->value] = [
                    'description' => $descriptions->getDescription($session->zone),
                    'value' => $zone['value'] + $session->getSeconds(),
                ];

                return $carry;
            },
            $empty
        );

        return new self($statistics);
    }

    /**
     * @return array{
     *  zone: string,
     *  description: string,
     *  duration_seconds: int,
     *  duration_percentage: float
     * }[]
     */
    public function toArray(int $total): array
    {
        return iterator_to_array($this->yieldStatistics($total));
    }

    /**
     * @return \Generator<array{
     *  zone: string,
     *  description: string,
     *  duration_seconds: int,
     *  duration_percentage: float
     * }>
     */
    private function yieldStatistics(int $total): \Generator
    {
        foreach ($this->statistics as $key => $zone) {
            $duration = $zone['value'];

            yield [
                'zone' => $key,
                'description' => $zone['description'],
                'duration_seconds' => $duration,
                'duration_percentage' => round($total > 0 ? ($duration / $total) * 100 : 0.0, 2),
            ];
        }
    }
}
