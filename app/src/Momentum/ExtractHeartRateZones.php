<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ExtractHeartRateZones
{
    /**
     * @param Session[] $heartRateSessions,
     */
    public function __construct(private array $heartRateSessions)
    {
    }

    /**
     * Get the HR zones for a given pace session.
     *
     * @return HeartRateZone[]
     */
    public function for(Session $paceSession): array
    {
        return array_reduce(
            $this->heartRateSessions,
            new class($paceSession->start, $paceSession->end) {
                public function __construct(
                    private \DateTimeImmutable $start,
                    private \DateTimeImmutable $end,
                ) {
                }

                /**
                 * @param HeartRateZone[] $carry
                 *
                 * @return HeartRateZone[]
                 */
                public function reduce(array $carry, Session $session): array
                {
                    if ($session->end > $this->start && $session->start < $this->end) {
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
