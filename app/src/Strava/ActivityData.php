<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Pace;
use App\Momentum\ZoneSession;
use App\Momentum\ZoneTransitionTracker;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use App\Strava\API\HeartRate;
use App\Strava\API\SpeedUnit;
use App\Strava\API\Velocity;

final class ActivityData
{
    private const MOVING_AVERAGE_PERIOD = 50;

    public function __construct(private Activity $activity, private ActivityStream $stream)
    {
    }

    public function getMetaData(): ActivityMetaData
    {
        return new ActivityMetaData($this->activity);
    }

    /**
     * @return array<
     *  array{
     *   timestamp: non-falsy-string,
     *   distance: float|null,
     *   heart_rate: HeartRate|null,
     *   heart_rate_avg: HeartRate|null,
     *   velocity: Velocity|null,
     *   velocity_avg: Velocity|null,
     *  }
     * >
     */
    public function getDataPoints(): array
    {
        return iterator_to_array($this->yieldDataPoints());
    }

    /**
     * @return \Generator<array{
     *  timestamp: non-falsy-string,
     *  distance: float|null,
     *  heart_rate: HeartRate|null,
     *  heart_rate_avg: HeartRate|null,
     *  velocity: Velocity|null,
     *  velocity_avg: Velocity|null,
     * }>
     */
    private function yieldDataPoints(): \Generator
    {
        foreach ($this->stream->yield($this->activity->getStartDateLocalTime(), self::MOVING_AVERAGE_PERIOD) as $point) {
            yield [
                'timestamp' => $point['timestamp']->format('H:i:s'),
                'distance' => $point['distance'],
                'heart_rate' => $point['heart_rate'],
                'heart_rate_avg' => $point['heart_rate_avg'],
                'velocity' => $point['velocity'],
                'velocity_avg' => $point['velocity_avg'],
            ];
        }
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityRawPoints(): array
    {
        return iterator_to_array($this->yieldVelocityRawPoints());
    }

    /**
     * @return \Generator<array{timestamp: string, value: float}>
     */
    private function yieldVelocityRawPoints(): \Generator
    {
        $type = $this->activity->data['type'];

        foreach ($this->yieldDataPoints() as $point) {
            if (null === $point['velocity']) {
                continue;
            }
            $velocity = $point['velocity'];

            $value = match ($type) {
                'Run', 'Walk', 'Hike', 'Swim' => $velocity->asPace()->value,
                'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR)->value,
                default => null,
            };

            if (null === $value) {
                continue;
            }

            yield [
                'timestamp' => $point['timestamp'],
                'value' => $value,
            ];
        }
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityAveragePoints(): array
    {
        return iterator_to_array($this->yieldVelocityAveragePoints());
    }

    /**
     * @return \Generator<array{timestamp: string, value: float}>
     */
    private function yieldVelocityAveragePoints(): \Generator
    {
        $type = $this->activity->data['type'];

        foreach ($this->yieldDataPoints() as $point) {
            if (null === $point['velocity_avg']) {
                continue;
            }
            $velocity = $point['velocity_avg'];

            $value = match ($type) {
                'Run', 'Walk', 'Hike', 'Swim' => $velocity->asPace()->value,
                'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR)->value,
                default => null,
            };

            if (null === $value) {
                continue;
            }

            yield [
                'timestamp' => $point['timestamp'],
                'value' => $value,
            ];
        }
    }

    /**
     * @return array<array{timestamp: string, value: int}>
     */
    public function getHeartRatePoints(): array
    {
        return iterator_to_array($this->yieldHeartRatePoints());
    }

    /**
     * @return \Generator<array{timestamp: string, value: int}>
     */
    private function yieldHeartRatePoints(): \Generator
    {
        foreach ($this->yieldDataPoints() as $point) {
            if (null === $point['heart_rate']) {
                continue;
            }

            $heartRate = $point['heart_rate'];

            yield [
                'timestamp' => $point['timestamp'],
                'value' => $heartRate->bpm,
            ];
        }
    }

    public function hasVelocityData(): bool
    {
        return !empty($this->getVelocityRawPoints());
    }

    public function hasHeartRateData(): bool
    {
        return !empty($this->getHeartRatePoints());
    }

    public function hasAnyData(): bool
    {
        return $this->hasVelocityData() || $this->hasHeartRateData();
    }

    public function getMovingAveragePeriod(): int
    {
        return self::MOVING_AVERAGE_PERIOD;
    }

    public function getHeartRateAverage(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }
        $sum = array_sum(array_map(fn ($point) => $point['value'], $points));

        return (int) round($sum / count($points));
    }

    public function getHeartRateMax(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }

        return max(array_map(fn ($point) => $point['value'], $points));
    }

    public function getVelocityAverage(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $sum = 0;
        $count = 0;
        foreach ($points as $point) {
            if (null === $point['velocity_avg']) {
                continue;
            }
            $sum += $point['velocity_avg']->metersPerSecond;
            ++$count;
        }
        $average = $count > 0 ? $sum / $count : 0;

        return new Velocity($average);
    }

    public function getVelocityMax(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $velocities = [];
        foreach ($points as $point) {
            if (null !== $point['velocity']) {
                $velocities[] = $point['velocity']->metersPerSecond;
            }
        }

        return Velocity::max($velocities);
    }

    /**
     * @return ZoneSession[]
     */
    public function getZoneSessions(
        Pace $ftp,
        ?int $maxHeartRate = null,
    ): array {
        $tracker = new ZoneTransitionTracker();

        foreach ($this->stream->yield($this->activity->getStartDateLocalTime(), self::MOVING_AVERAGE_PERIOD) as $point) {
            $velocity = $point['velocity'];
            $heartRate = $point['heart_rate'];

            if (null === $velocity || $velocity->metersPerSecond < 0.1) {
                continue;
            }

            // Use velocity_smooth from Strava (velocity_avg from ActivityStream)
            $smoothedVelocity = $point['velocity_avg'] ?? $velocity;

            if ($smoothedVelocity->metersPerSecond < 0.1) {
                continue;
            }

            // Convert to pace and reject unreasonably slow paces (> 60 min/km)
            $pace = $smoothedVelocity->asPace();
            if ($pace->value > 60.0) {
                continue;
            }

            $tracker->processPoint(
                timestamp: $point['timestamp'],
                pace: $pace,
                ftp: $ftp,
                heartRate: $heartRate?->bpm,
                maxHeartRate: $maxHeartRate,
            );
        }

        $tracker->finalize($this->activity->getStartDateLocalTime()->add(
            new \DateInterval('PT'.$this->activity->data['elapsed_time'].'S')
        ));

        return $tracker->getPaceZoneSessions();
    }

    /**
     * @return ZoneSession[]
     */
    public function getHeartRateZoneSessions(?int $maxHeartRate = null): array
    {
        if (null === $maxHeartRate) {
            $maxHeartRate = $this->getHeartRateMax();
        }

        if ($maxHeartRate <= 0) {
            return [];
        }

        $tracker = new ZoneTransitionTracker();
        // Dummy FTP for HR zone tracking (not used in HR calculation)
        $dummyFtp = new Pace(6.0);

        foreach ($this->stream->yield($this->activity->getStartDateLocalTime(), self::MOVING_AVERAGE_PERIOD) as $point) {
            $heartRate = $point['heart_rate'];

            if (null === $heartRate) {
                continue;
            }

            // Use dummy pace (not used for HR zones)
            $dummyPace = new Pace(6.0);

            $tracker->processPoint(
                timestamp: $point['timestamp'],
                pace: $dummyPace,
                ftp: $dummyFtp,
                heartRate: $heartRate->bpm,
                maxHeartRate: $maxHeartRate,
            );
        }

        $tracker->finalize($this->activity->getStartDateLocalTime()->add(
            new \DateInterval('PT'.$this->activity->data['elapsed_time'].'S')
        ));

        return $tracker->getHeartRateZoneSessions();
    }
}
