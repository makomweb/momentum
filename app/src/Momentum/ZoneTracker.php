<?php

declare(strict_types=1);

namespace App\Momentum;

final class ZoneTracker
{
    private const int GAP_THRESHOLD = 5; // seconds

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
                $this->closeCurrentZone();
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

        $this->closeCurrentZone();
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
            zone: $this->currentZone
        );
    }

    private function closeCurrentZone(): void
    {
        if (null === $this->currentZone || null === $this->zoneEnteredAt) {
            return;
        }

        assert($this->lastPointTimestamp instanceof \DateTimeImmutable, 'Was not opened yet!');

        $this->sessions[] = new ZoneSession(
            startTime: $this->zoneEnteredAt,
            endTime: $this->lastPointTimestamp,
            zone: $this->currentZone
        );
    }

    /** @return ZoneSession[] */
    public function getSessions(): array
    {
        return $this->sessions;
    }
}
