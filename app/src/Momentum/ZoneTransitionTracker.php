<?php

declare(strict_types=1);

namespace App\Momentum;

final class ZoneTransitionTracker
{
    private ?PaceZone $currentPaceZone = null;
    private ?\DateTimeImmutable $paceZoneEnteredAt = null;
    /** @var ZoneSession[] */
    private array $paceZoneSessions = [];

    private ?HeartRateZone $currentHeartRateZone = null;
    private ?\DateTimeImmutable $heartRateZoneEnteredAt = null;
    /** @var ZoneSession[] */
    private array $heartRateZoneSessions = [];

    public function processPoint(
        \DateTimeImmutable $timestamp,
        Pace $pace,
        Pace $ftp,
        ?int $heartRate = null,
        ?int $maxHeartRate = null,
    ): void {
        // Process pace zone
        $newPaceZone = PaceZone::calculate($pace, $ftp);
        $this->updatePaceZone($newPaceZone, $timestamp);

        // Process heart rate zone (if data available)
        if (null !== $heartRate && null !== $maxHeartRate && $maxHeartRate > 0) {
            $newHeartRateZone = HeartRateZone::calculate($heartRate, $maxHeartRate);
            $this->updateHeartRateZone($newHeartRateZone, $timestamp);
        }
    }

    private function updatePaceZone(PaceZone $newZone, \DateTimeImmutable $timestamp): void
    {
        // If zone hasn't changed, do nothing
        if ($newZone === $this->currentPaceZone) {
            return;
        }

        // Record the previous zone session if one exists
        if (null !== $this->currentPaceZone && null !== $this->paceZoneEnteredAt) {
            $durationSeconds = $timestamp->getTimestamp() - $this->paceZoneEnteredAt->getTimestamp();
            $this->paceZoneSessions[] = new ZoneSession(
                startTime: $this->paceZoneEnteredAt,
                endTime: $timestamp,
                zone: $this->currentPaceZone,
                durationSeconds: $durationSeconds,
            );
        }

        // Start new zone session
        $this->currentPaceZone = $newZone;
        $this->paceZoneEnteredAt = $timestamp;
    }

    private function updateHeartRateZone(HeartRateZone $newZone, \DateTimeImmutable $timestamp): void
    {
        // If zone hasn't changed, do nothing
        if ($newZone === $this->currentHeartRateZone) {
            return;
        }

        // Record the previous zone session if one exists
        if (null !== $this->currentHeartRateZone && null !== $this->heartRateZoneEnteredAt) {
            $durationSeconds = $timestamp->getTimestamp() - $this->heartRateZoneEnteredAt->getTimestamp();
            $this->heartRateZoneSessions[] = new ZoneSession(
                startTime: $this->heartRateZoneEnteredAt,
                endTime: $timestamp,
                zone: $this->currentHeartRateZone,
                durationSeconds: $durationSeconds,
            );
        }

        // Start new zone session
        $this->currentHeartRateZone = $newZone;
        $this->heartRateZoneEnteredAt = $timestamp;
    }

    public function finalize(\DateTimeImmutable $endTime): void
    {
        // Record any open pace zone session
        if (null !== $this->currentPaceZone && null !== $this->paceZoneEnteredAt) {
            $durationSeconds = $endTime->getTimestamp() - $this->paceZoneEnteredAt->getTimestamp();
            $this->paceZoneSessions[] = new ZoneSession(
                startTime: $this->paceZoneEnteredAt,
                endTime: $endTime,
                zone: $this->currentPaceZone,
                durationSeconds: $durationSeconds,
            );
        }

        // Record any open heart rate zone session
        if (null !== $this->currentHeartRateZone && null !== $this->heartRateZoneEnteredAt) {
            $durationSeconds = $endTime->getTimestamp() - $this->heartRateZoneEnteredAt->getTimestamp();
            $this->heartRateZoneSessions[] = new ZoneSession(
                startTime: $this->heartRateZoneEnteredAt,
                endTime: $endTime,
                zone: $this->currentHeartRateZone,
                durationSeconds: $durationSeconds,
            );
        }
    }

    /**
     * @return ZoneSession[]
     */
    public function getPaceZoneSessions(): array
    {
        return $this->paceZoneSessions;
    }

    /**
     * @return ZoneSession[]
     */
    public function getHeartRateZoneSessions(): array
    {
        return $this->heartRateZoneSessions;
    }
}
