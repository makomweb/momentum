<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
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
     *   distance: float,
     *   heart_rate: int,
     *   heart_rate_avg: float,
     *   velocity: float,
     *   velocity_avg: float,
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
     *  distance: float,
     *  heart_rate: int,
     *  heart_rate_avg: float,
     *  velocity: float,
     *  velocity_avg: float,
     * }>
     */
    private function yieldDataPoints(): \Generator
    {
        $activityType = $this->activity->data['type'];

        foreach ($this->stream->yield($this->activity->getStartDateLocalTime(), self::MOVING_AVERAGE_PERIOD) as $point) {
            yield [
                'timestamp' => $point['timestamp']->format('H:i:s'),
                'distance' => $point['distance'],
                'heart_rate' => $point['heart_rate'],
                'heart_rate_avg' => $point['heart_rate_avg'],
                'velocity' => $point['velocity'], // Velocity::fromStrava($point['velocity'], $activityType)->value,
                'velocity_avg' => $point['velocity_avg'], // Velocity::fromStrava($point['velocity_avg'], $activityType)->value,
                // 'velocity' => Velocity::fromStrava($point['velocity'], $activityType)->value,
                // 'velocity_avg' => Velocity::fromStrava($point['velocity_avg'], $activityType)->value,
            ];
        }
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityRawPoints(): array
    {
        $points = [];
        foreach ($this->getDataPoints() as $point) {
            if ($point['velocity'] > 0) {
                $points[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['velocity'], 2),
                ];
            }
        }

        return $points;
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityAveragePoints(): array
    {
        $points = [];
        foreach ($this->getDataPoints() as $point) {
            if ($point['velocity_avg'] > 0) {
                $points[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['velocity_avg'], 2),
                ];
            }
        }

        return $points;
    }

    /**
     * @return array<array{timestamp: string, value: int}>
     */
    public function getHeartRatePoints(): array
    {
        $points = [];
        foreach ($this->getDataPoints() as $point) {
            if ($point['heart_rate'] > 0 || $point['heart_rate_avg'] > 0) {
                $points[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => (int) ($point['heart_rate_avg'] ?: $point['heart_rate']),
                ];
            }
        }

        return $points;
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
}
