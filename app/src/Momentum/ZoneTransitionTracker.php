<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneTransitionTracker
{
    private ZoneTracker $paceTracker;
    private ZoneTracker $heartRateTracker;

    private function __construct(private Pace $treshold, private HeartRate $maxHeartRate)
    {
        $this->paceTracker = new ZoneTracker();
        $this->heartRateTracker = new ZoneTracker();
    }

    public static function for(AthleteInterface $athlete): self
    {
        return new self($athlete->getTreshold(), $athlete->getMaxHeartRate());
    }

    public function processPoint(
        \DateTimeImmutable $timestamp,
        Pace $pace,
        HeartRate $heartRate = new HeartRate(),
    ): void {
        $this->paceTracker->record($pace->getZone($this->treshold), $timestamp);

        if ($heartRate->isValid() && $this->maxHeartRate->isValid()) {
            $this->heartRateTracker->record($heartRate->getZone($this->maxHeartRate), $timestamp);
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
