<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ActiveHeartRateZones
{
    private function __construct(
        private \DateTimeImmutable $startTime,
        private \DateTimeImmutable $endTime,
    ) {
    }

    /**
     * Get the HR zones for a given time period.
     *
     * @param ZoneSession[] $sessions,
     *
     * @return HeartRateZone[]
     */
    public static function getZones(array $sessions, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime): array
    {
        return array_reduce(
            $sessions,
            new self($startTime, $endTime)->reduce(...),
            []
        );
    }

    /**
     * @param HeartRateZone[] $carry
     *
     * @return HeartRateZone[]
     */
    private function reduce(array $carry, ZoneSession $session): array
    {
        if ($session->endTime > $this->startTime && $session->startTime < $this->endTime) {
            if ($session->zone instanceof HeartRateZone) {
                $carry[$session->zone->value] = $session->zone;
            }
        }

        return $carry;
    }
}
