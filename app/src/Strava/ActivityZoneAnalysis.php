<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use App\Momentum\ZoneSession;

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

    public function getPaceZoneStatistics(): PaceZoneStatistics
    {
        $stats = [];

        foreach (PaceZone::cases() as $zone) {
            $timeInZone = 0;
            foreach ($this->paceZoneSessions as $session) {
                if ($session->isInZone($zone)) {
                    $timeInZone += $session->durationSeconds;
                }
            }
            $stats[$zone->value] = [
                'zone' => $zone,
                'duration_seconds' => $timeInZone,
                'duration_percentage' => $this->totalActivitySeconds > 0
                    ? ($timeInZone / $this->totalActivitySeconds) * 100
                    : 0.0,
            ];
        }

        return new PaceZoneStatistics($stats, $this->totalActivitySeconds, $this->paceZoneSessions);
    }

    public function getHeartRateZoneStatistics(): HeartRateZoneStatistics
    {
        $stats = [];

        foreach (HeartRateZone::cases() as $zone) {
            $timeInZone = 0;
            foreach ($this->heartRateZoneSessions as $session) {
                if ($session->isInZone($zone)) {
                    $timeInZone += $session->durationSeconds;
                }
            }
            $stats[$zone->value] = [
                'zone' => $zone,
                'duration_seconds' => $timeInZone,
                'duration_percentage' => $this->totalActivitySeconds > 0
                    ? ($timeInZone / $this->totalActivitySeconds) * 100
                    : 0.0,
            ];
        }

        return new HeartRateZoneStatistics($stats, $this->totalActivitySeconds, $this->heartRateZoneSessions);
    }

    /**
     * Get polarized training metrics: % easy (zones 1-2) vs hard (zones 4-5).
     */
    public function getPolarizedTrainingMetrics(): PolarizedTrainingMetrics
    {
        $easyTime = 0;
        $hardTime = 0;

        foreach ($this->paceZoneSessions as $session) {
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
