<?php

declare(strict_types=1);

namespace App\Momentum;

final class ZoneTransitionTracker
{
    private const GAP_THRESHOLD = 10; // seconds - max allowed gap between data points

    private ?PaceZone $currentPaceZone = null;
    private ?\DateTimeImmutable $paceZoneEnteredAt = null;
    private ?\DateTimeImmutable $lastPacePointTimestamp = null;
    /** @var ZoneSession[] */
    private array $paceZoneSessions = [];

    private ?HeartRateZone $currentHeartRateZone = null;
    private ?\DateTimeImmutable $heartRateZoneEnteredAt = null;
    private ?\DateTimeImmutable $lastHeartRatePointTimestamp = null;
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
        // Check for gap in data - if gap is larger than threshold, force finalize current zone
        if (null !== $this->lastPacePointTimestamp) {
            $gap = $timestamp->getTimestamp() - $this->lastPacePointTimestamp->getTimestamp();
            if ($gap > self::GAP_THRESHOLD) {
                // Force finalize the current zone at the last known point
                if (null !== $this->currentPaceZone && null !== $this->paceZoneEnteredAt) {
                    $durationSeconds = $this->lastPacePointTimestamp->getTimestamp() - $this->paceZoneEnteredAt->getTimestamp();
                    $this->paceZoneSessions[] = new ZoneSession(
                        startTime: $this->paceZoneEnteredAt,
                        endTime: $this->lastPacePointTimestamp,
                        zone: $this->currentPaceZone,
                        durationSeconds: $durationSeconds,
                    );
                }
                // Start new zone at current timestamp
                $this->currentPaceZone = $newZone;
                $this->paceZoneEnteredAt = $timestamp;
                $this->lastPacePointTimestamp = $timestamp;
                return;
            }
        }

        $this->lastPacePointTimestamp = $timestamp;

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
        // Check for gap in data - if gap is larger than threshold, force finalize current zone
        if (null !== $this->lastHeartRatePointTimestamp) {
            $gap = $timestamp->getTimestamp() - $this->lastHeartRatePointTimestamp->getTimestamp();
            if ($gap > self::GAP_THRESHOLD) {
                // Force finalize the current zone at the last known point
                if (null !== $this->currentHeartRateZone && null !== $this->heartRateZoneEnteredAt) {
                    $durationSeconds = $this->lastHeartRatePointTimestamp->getTimestamp() - $this->heartRateZoneEnteredAt->getTimestamp();
                    $this->heartRateZoneSessions[] = new ZoneSession(
                        startTime: $this->heartRateZoneEnteredAt,
                        endTime: $this->lastHeartRatePointTimestamp,
                        zone: $this->currentHeartRateZone,
                        durationSeconds: $durationSeconds,
                    );
                }
                // Start new zone at current timestamp
                $this->currentHeartRateZone = $newZone;
                $this->heartRateZoneEnteredAt = $timestamp;
                $this->lastHeartRatePointTimestamp = $timestamp;
                return;
            }
        }

        $this->lastHeartRatePointTimestamp = $timestamp;

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
            $paceEndTime = $endTime;
            if (null !== $this->lastPacePointTimestamp) {
                $tailGap = $endTime->getTimestamp() - $this->lastPacePointTimestamp->getTimestamp();
                if ($tailGap < 0 || $tailGap > self::GAP_THRESHOLD) {
                    $paceEndTime = $this->lastPacePointTimestamp;
                }
            }

            $durationSeconds = $paceEndTime->getTimestamp() - $this->paceZoneEnteredAt->getTimestamp();
            $this->paceZoneSessions[] = new ZoneSession(
                startTime: $this->paceZoneEnteredAt,
                endTime: $paceEndTime,
                zone: $this->currentPaceZone,
                durationSeconds: $durationSeconds,
            );
        }

        // Record any open heart rate zone session
        if (null !== $this->currentHeartRateZone && null !== $this->heartRateZoneEnteredAt) {
            $heartRateEndTime = $endTime;
            if (null !== $this->lastHeartRatePointTimestamp) {
                $tailGap = $endTime->getTimestamp() - $this->lastHeartRatePointTimestamp->getTimestamp();
                if ($tailGap < 0 || $tailGap > self::GAP_THRESHOLD) {
                    $heartRateEndTime = $this->lastHeartRatePointTimestamp;
                }
            }

            $durationSeconds = $heartRateEndTime->getTimestamp() - $this->heartRateZoneEnteredAt->getTimestamp();
            $this->heartRateZoneSessions[] = new ZoneSession(
                startTime: $this->heartRateZoneEnteredAt,
                endTime: $heartRateEndTime,
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
