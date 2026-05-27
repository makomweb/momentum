<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneAnalysis
{
    /**
     * @param ZoneSession[] $paceZoneSessions
     * @param ZoneSession[] $heartRateZoneSessions
     */
    public function __construct(
        private array $paceZoneSessions,
        private array $heartRateZoneSessions,
        private int $totalActivitySeconds,
    ) {
    }

    public function getPaceZoneStatistics(): PaceZoneStatistics
    {
        return PaceZoneStatistics::fromSessions($this->paceZoneSessions, $this->totalActivitySeconds);
    }

    public function getHeartRateZoneStatistics(): HeartRateZoneStatistics
    {
        return HeartRateZoneStatistics::fromSessions($this->heartRateZoneSessions, $this->totalActivitySeconds);
    }

    /**
     * Get pace zone sessions that are confirmed by matching HR zones.
     * Filters out false positives (e.g., high pace spikes with low HR).
     *
     * @return ZoneSession[]
     */
    public function getConfirmedPaceZoneSessions(): array
    {
        $yielder = new YieldConfirmedPaceZoneSessions($this->paceZoneSessions, $this->heartRateZoneSessions);

        return iterator_to_array($yielder->yieldSessions());
    }

    /**
     * Get training metrics: % easy (zones 1-2) vs tempo vs hard (zones 4-5).
     * Uses only confirmed pace zones to avoid false positives.
     */
    public function getTrainingMetrics(): TrainingMetrics
    {
        $sessions = $this->getConfirmedPaceZoneSessions();

        return TrainingMetrics::fromSessions($sessions, $this->totalActivitySeconds);
    }
}
