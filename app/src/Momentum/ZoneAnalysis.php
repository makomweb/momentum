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
        public array $paceZoneSessions,
        public array $heartRateZoneSessions,
        public int $totalSeconds,
    ) {
    }

    public function getPaceZoneStatistics(): ZoneStatistics
    {
        return ZoneStatistics::fromSessions($this->paceZoneSessions, $this->totalSeconds, PaceZone::cases());
    }

    public function getHeartRateZoneStatistics(): ZoneStatistics
    {
        return ZoneStatistics::fromSessions($this->heartRateZoneSessions, $this->totalSeconds, HeartRateZone::cases());
    }

    /**
     * Get pace zone sessions that are confirmed by matching HR zones.
     * Filters out false positives (e.g., high pace spikes with low HR).
     *
     * @return ZoneSession[]
     */
    public function getConfirmedPaceZoneSessions(): array
    {
        $confirmed = new ConfirmedPaceZoneSessions($this->paceZoneSessions, $this->heartRateZoneSessions);

        return iterator_to_array($confirmed->yieldSessions());
    }

    /**
     * Get training metrics: % easy (zones 1-2) vs tempo vs hard (zones 4-5).
     * Uses only confirmed pace zones to avoid false positives.
     */
    public function getTrainingMetrics(): TrainingMetrics
    {
        $sessions = $this->getConfirmedPaceZoneSessions();

        return TrainingMetrics::fromSessions($sessions, $this->totalSeconds);
    }
}
