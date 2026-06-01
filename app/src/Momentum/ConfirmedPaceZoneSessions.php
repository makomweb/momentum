<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ConfirmedPaceZoneSessions
{
    /**
     * @param ZoneSession[] $paceZoneSessions
     * @param ZoneSession[] $heartRateZoneSessions
     */
    private function __construct(
        public array $paceZoneSessions,
        public array $heartRateZoneSessions,
    ) {
    }

    public static function from(ZoneSessions $sessions): self
    {
        return new self($sessions->getPaceZoneSessions(), $sessions->getHeartRateSessions());
    }

    /**
     * Yield pace zone sessions that are confirmed by matching HR zones.
     * Filters out false positives (e.g., high pace spikes with low HR).
     *
     * @return \Generator<ZoneSession>
     */
    public function yieldSessions(): \Generator
    {
        foreach ($this->paceZoneSessions as $paceZoneSession) {
            if ($paceZoneSession->isConfirmedByOneOf($this->heartRateZoneSessions)) {
                yield $paceZoneSession;
            }
        }
    }
}
