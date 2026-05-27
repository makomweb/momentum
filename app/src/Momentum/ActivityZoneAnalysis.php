<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ActivityZoneAnalysis
{
    /**
     * @param ZoneSession[] $paceZoneSessions
     * @param ZoneSession[] $heartRateZoneSessions
     */
    public function __construct(
        public array $paceZoneSessions,
        public array $heartRateZoneSessions,
        public int $totalActivitySeconds,
    ) {
    }

    /**
     * Get pace zone sessions that are confirmed by matching HR zones.
     * Filters out false positives (e.g., high pace spikes with low HR).
     *
     * @return ZoneSession[]
     */
    public function getConfirmedPaceZoneSessions(): array
    {
        $confirmed = [];

        foreach ($this->paceZoneSessions as $paceSession) {
            // Find all HR sessions that overlap with this pace session
            $overlappingHrZones = $this->getOverlappingHrZones(
                $paceSession->startTime,
                $paceSession->endTime,
            );

            // Check if the pace zone is confirmed by any overlapping HR zone
            $isConfirmed = false;
            foreach ($overlappingHrZones as $hrZone) {
                if ($paceSession->zone instanceof PaceZone && ZoneValidator::isPaceZoneConfirmed($paceSession->zone, $hrZone)) {
                    $isConfirmed = true;
                    break;
                }
            }

            if ($isConfirmed) {
                $confirmed[] = $paceSession;
            }
        }

        return $confirmed;
    }

    /**
     * Get which HR zones were active during a time period.
     *
     * @return HeartRateZone[]
     */
    private function getOverlappingHrZones(
        \DateTimeImmutable $startTime,
        \DateTimeImmutable $endTime,
    ): array {
        $zones = [];

        foreach ($this->heartRateZoneSessions as $session) {
            // Check if session overlaps with the time window
            if ($session->endTime > $startTime && $session->startTime < $endTime) {
                if ($session->zone instanceof HeartRateZone) {
                    $zones[$session->zone->value] = $session->zone;
                }
            }
        }

        return array_values($zones);
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
     * Get polarized training metrics: % easy (zones 1-2) vs hard (zones 4-5).
     * Uses only confirmed pace zones to avoid false positives.
     */
    public function getPolarizedTrainingMetrics(): PolarizedTrainingMetrics
    {
        $confirmedSessions = $this->getConfirmedPaceZoneSessions();
        $easyTime = 0;
        $hardTime = 0;

        foreach ($confirmedSessions as $session) {
            if ($session->isInZone(PaceZone::ZONE_1) || $session->isInZone(PaceZone::ZONE_2)) {
                $easyTime += $session->durationSeconds;
            } elseif ($session->isInZone(PaceZone::ZONE_4) || $session->isInZone(PaceZone::ZONE_5)) {
                $hardTime += $session->durationSeconds;
            }
        }

        return new PolarizedTrainingMetrics(
            easySeconds: $easyTime,
            hardSeconds: $hardTime,
            totalSeconds: $this->totalActivitySeconds,
        );
    }
}
