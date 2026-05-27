<?php

declare(strict_types=1);

namespace App\Momentum;

/**
 * Tracks time spent in a single zone type (pace or heart rate).
 * Handles gap detection and zone session recording.
 */
final class ZoneTracker
{
    private const int GAP_THRESHOLD = 10; // seconds

    private PaceZone|HeartRateZone|null $currentZone = null;
    private ?\DateTimeImmutable $zoneEnteredAt = null;
    private ?\DateTimeImmutable $lastPointTimestamp = null;

    /** @var ZoneSession[] */
    private array $sessions = [];

    public function record(PaceZone|HeartRateZone $zone, \DateTimeImmutable $timestamp): void
    {
        if (null !== $this->lastPointTimestamp) {
            $gap = $timestamp->getTimestamp() - $this->lastPointTimestamp->getTimestamp();
            if ($gap > self::GAP_THRESHOLD) {
                $this->closeCurrentZone($this->lastPointTimestamp);
                $this->currentZone = $zone;
                $this->zoneEnteredAt = $timestamp;
                $this->lastPointTimestamp = $timestamp;

                return;
            }
        }

        $this->lastPointTimestamp = $timestamp;

        if ($zone === $this->currentZone) {
            return;
        }

        $this->closeCurrentZone($timestamp);
        $this->currentZone = $zone;
        $this->zoneEnteredAt = $timestamp;
    }

    public function finalize(\DateTimeImmutable $endTime): void
    {
        if (null === $this->currentZone || null === $this->zoneEnteredAt) {
            return;
        }

        $effectiveEnd = $endTime;
        if (null !== $this->lastPointTimestamp) {
            $tailGap = $endTime->getTimestamp() - $this->lastPointTimestamp->getTimestamp();
            if ($tailGap < 0 || $tailGap > self::GAP_THRESHOLD) {
                $effectiveEnd = $this->lastPointTimestamp;
            }
        }

        $this->sessions[] = new ZoneSession(
            startTime: $this->zoneEnteredAt,
            endTime: $effectiveEnd,
            zone: $this->currentZone,
            durationSeconds: $effectiveEnd->getTimestamp() - $this->zoneEnteredAt->getTimestamp(),
        );
    }

    /**
     * @return ZoneSession[]
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }

    private function closeCurrentZone(\DateTimeImmutable $endTime): void
    {
        if (null === $this->currentZone || null === $this->zoneEnteredAt) {
            return;
        }

        $this->sessions[] = new ZoneSession(
            startTime: $this->zoneEnteredAt,
            endTime: $endTime,
            zone: $this->currentZone,
            durationSeconds: $endTime->getTimestamp() - $this->zoneEnteredAt->getTimestamp(),
        );
    }
}
