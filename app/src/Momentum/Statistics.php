<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Statistics
{
    /**
     * @param array<string, int>            $statistics
     * @param array<HeartRateZone|PaceZone> $zones
     */
    private function __construct(private array $statistics, private array $zones)
    {
    }

    /**
     * @param Session[]                     $sessions
     * @param array<HeartRateZone|PaceZone> $zones
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
            static function (array $carry, HeartRateZone|PaceZone $zone): array {
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
            static function (array $carry, Session $session): array {
                assert(is_int($carry[$session->zone->value]));

                $carry[$session->zone->value] = $carry[$session->zone->value] + $session->getSeconds();

                return $carry;
            },
            $empty
        );

        return new self($statistics, $zones);
    }

    /**
     * @return array{
     *  type: string,
     *  zone: int|string,
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
     *  type: string,
     *  zone: int|string,
     *  duration_seconds: int,
     *  duration_percentage: float
     * }>
     */
    private function yieldStatistics(int $total): \Generator
    {
        foreach ($this->statistics as $zone => $seconds) {
            yield [
                'type' => array_first($this->zones) instanceof HeartRateZone ? HeartRateZone::class : PaceZone::class,
                'zone' => $zone,
                'duration_seconds' => $seconds,
                'duration_percentage' => round($total > 0 ? ($seconds / $total) * 100 : 0.0, 2),
            ];
        }
    }
}
