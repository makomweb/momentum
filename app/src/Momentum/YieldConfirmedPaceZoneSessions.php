<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class YieldConfirmedPaceZoneSessions
{
    /**
     * @param ZoneSession[] $paceZoneSessions
     * @param ZoneSession[] $heartRateZoneSessions
     */
    public function __construct(
        public array $paceZoneSessions,
        public array $heartRateZoneSessions,
    ) {
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
