<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Period;
use App\Momentum\Zone;

final readonly class ZoneDetector
{
    // Simple dual-criterion thresholds for robust zone detection
    // HIGH intensity: velocity + heart rate both elevated
    // Thresholds calibrated from test activity data
    private const HIGH_VELOCITY_MIN = 3.3;   // m/s
    private const HIGH_HEART_RATE_MIN = 148; // bpm - adjusted lower to catch build-up

    /**
     * Detect zones by combining velocity and heart rate data.
     * Returns periods grouped by HIGH vs LOW intensity.
     *
     * @return array<Period>
     */
    public function detect(ActivityData $activity): array
    {
        $periods = [];
        $currentZone = null;
        $currentStart = null;

        foreach ($activity->yieldRawDataPoints() as $point) {
            $velocity = $point['velocity_avg'];
            $heartRate = $point['heart_rate_avg'];
            $timestamp = $point['timestamp'];

            // Skip warmup phase (very low velocity)
            if ($velocity < 0.5) {
                continue;
            }

            // Determine if we're in a high-intensity zone using both metrics
            $isHighIntensity = $velocity >= self::HIGH_VELOCITY_MIN
                               && $heartRate >= self::HIGH_HEART_RATE_MIN;

            // Classify as either HIGH_INTERVAL or MODERATE (for now)
            $zone = $isHighIntensity ? Zone::LONG_INTERVAL : Zone::MODERATE;

            // Zone change
            if ($currentZone !== $zone && null !== $currentZone) {
                if (null !== $currentStart) {
                    $periods[] = new Period($currentStart, $timestamp, $currentZone);
                }
                $currentZone = $zone;
                $currentStart = $timestamp;
            } // First zone
            elseif (null === $currentZone) {
                $currentZone = $zone;
                $currentStart = $timestamp;
            }
        }

        // Add the last period
        if (null !== $currentZone && null !== $currentStart) {
            $lastTimestamp = $this->getLastValidTimestamp($activity);
            if (null !== $lastTimestamp) {
                $periods[] = new Period($currentStart, $lastTimestamp, $currentZone);
            }
        }

        // Merge small periods and consecutive same-zone periods
        return $this->mergePeriods($periods, 120);
    }

    private function getLastValidTimestamp(ActivityData $activity): ?\DateTimeImmutable
    {
        $lastTimestamp = null;
        foreach ($activity->yieldRawDataPoints() as $point) {
            if ($point['velocity_avg'] >= 0.5) {
                $lastTimestamp = $point['timestamp'];
            }
        }

        return $lastTimestamp;
    }

    /**
     * Merge periods that are smaller than minimum duration and collapse consecutive same zones.
     *
     * @param array<Period> $periods
     * @param int           $minSeconds minimum period duration in seconds
     *
     * @return array<Period>
     */
    private function mergePeriods(array $periods, int $minSeconds): array
    {
        if (count($periods) <= 1) {
            return $periods;
        }

        $merged = [];
        $periodCount = count($periods);
        $i = 0;

        while ($i < $periodCount) {
            /** @var Period $period */
            $period = $periods[$i];
            $duration = $period->end->getTimestamp() - $period->start->getTimestamp();

            if ($duration < $minSeconds) {
                // This period is too small, absorb it into a larger neighbor
                if (!empty($merged)) {
                    // Extend the previous period
                    $prev = $merged[count($merged) - 1];
                    $merged[count($merged) - 1] = new Period($prev->start, $period->end, $prev->zone);
                } elseif ($i + 1 < $periodCount) {
                    // Use next period's zone for this small one
                    /** @var Period $nextPeriod */
                    $nextPeriod = $periods[$i + 1];
                    $merged[] = new Period($period->start, $period->end, $nextPeriod->zone);
                } else {
                    // Last small period with no next, keep as is
                    $merged[] = $period;
                }
            } else {
                $merged[] = $period;
            }

            ++$i;
        }

        // Post-process: collapse consecutive periods with same zone
        return $this->collapseConsecutiveZones($merged);
    }

    /**
     * Merge consecutive periods with the same zone.
     *
     * @param array<Period> $periods
     *
     * @return array<Period>
     */
    private function collapseConsecutiveZones(array $periods): array
    {
        if (count($periods) <= 1) {
            return $periods;
        }

        $collapsed = [];
        $current = $periods[0];

        for ($i = 1; $i < count($periods); ++$i) {
            if ($periods[$i]->zone === $current->zone) {
                // Extend current period
                $current = new Period($current->start, $periods[$i]->end, $current->zone);
            } else {
                // Zone changed, save current and start new
                $collapsed[] = $current;
                $current = $periods[$i];
            }
        }

        // Don't forget the last one
        $collapsed[] = $current;

        return $collapsed;
    }
}
