<?php

declare(strict_types=1);

namespace App\Momentum;

final class ZoneTransitionTracker
{
    private readonly ZoneTracker $paceTracker;
    private readonly ZoneTracker $heartRateTracker;

    public function __construct()
    {
        $this->paceTracker = new ZoneTracker();
        $this->heartRateTracker = new ZoneTracker();
    }

    public function processPoint(
        \DateTimeImmutable $timestamp,
        Pace $pace,
        Pace $ftp,
        ?int $heartRate = null,
        ?int $maxHeartRate = null,
    ): void {
        $this->paceTracker->record(PaceZone::calculate($pace, $ftp), $timestamp);

        if (null !== $heartRate && null !== $maxHeartRate && $maxHeartRate > 0) {
            $this->heartRateTracker->record(HeartRateZone::calculate($heartRate, $maxHeartRate), $timestamp);
        }
    }

    public function finalize(\DateTimeImmutable $endTime): void
    {
        $this->paceTracker->finalize($endTime);
        $this->heartRateTracker->finalize($endTime);
    }

    /**
     * @return ZoneSession[]
     */
    public function getPaceZoneSessions(): array
    {
        return $this->paceTracker->getSessions();
    }

    /**
     * @return ZoneSession[]
     */
    public function getHeartRateZoneSessions(): array
    {
        return $this->heartRateTracker->getSessions();
    }
}
