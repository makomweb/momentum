<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ExtractHeartRateZones
{
    /**
     * @param ZoneSession[] $heartRateSessions,
     */
    public function __construct(private array $heartRateSessions)
    {
    }

    /**
     * Get the HR zones for a given pace session.
     *
     * @return HeartRateZone[]
     */
    public function for(ZoneSession $paceSession): array
    {
        return array_reduce(
            $this->heartRateSessions,
            new class($paceSession->startTime, $paceSession->endTime) {
                public function __construct(
                    private \DateTimeImmutable $startTime,
                    private \DateTimeImmutable $endTime,
                ) {
                }

                /**
                 * @param HeartRateZone[] $carry
                 *
                 * @return HeartRateZone[]
                 */
                public function reduce(array $carry, ZoneSession $session): array
                {
                    if ($session->endTime > $this->startTime && $session->startTime < $this->endTime) {
                        if ($session->zone instanceof HeartRateZone) {
                            $carry[$session->zone->value] = $session->zone;
                        }
                    }

                    return $carry;
                }
            }->reduce(...),
            []
        );
    }
}
