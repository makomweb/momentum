<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use App\Strava\API\HeartFrequency;
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
     *   distance: float,
     *   heart_rate: HeartFrequency,
     *   heart_rate_avg: HeartFrequency,
     *   velocity: Velocity,
     *   velocity_avg: Velocity,
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
     *  heart_rate: HeartFrequency,
     *  heart_rate_avg: HeartFrequency,
     *  velocity: Velocity,
     *  velocity_avg: Velocity,
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
        $type = $this->activity->data['type'];

        $points = [];
        foreach ($this->getDataPoints() as $point) {
            $velocity = $point['velocity'];
            $points[] = [
                'timestamp' => $point['timestamp'],
                'value' => match ($type) {
                    'Run', 'Walk', 'Hike', 'Swim' => $velocity->asPace()->value,
                    'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR)->value,
                    default => throw new \Exception(sprintf('Unsupported type "%s"!', $type)),
                },
            ];
        }

        return $points;
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityAveragePoints(): array
    {
        $type = $this->activity->data['type'];

        $points = [];
        foreach ($this->getDataPoints() as $point) {
            $velocity = $point['velocity_avg'];

            $points[] = [
                'timestamp' => $point['timestamp'],
                'value' => match ($type) {
                    'Run', 'Walk', 'Hike', 'Swim' => $velocity->asPace()->value,
                    'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR)->value,
                    default => throw new \Exception(sprintf('Unsupported type "%s"!', $type)),
                },
            ];
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
            $heartRate = $point['heart_rate'];

            $points[] = [
                'timestamp' => $point['timestamp'],
                'value' => $heartRate->value,
            ];
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

    public function getHeartRateAverage(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }
        $sum = array_sum(array_map(fn ($p) => $p['value'], $points));

        return (int) round($sum / count($points));
    }

    public function getHeartRateMax(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }

        return max(array_map(fn ($p) => $p['value'], $points));
    }

    public function getVelocityAverage(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $sum = 0;
        foreach ($points as $point) {
            $sum += $point['velocity_avg']->metersPerSecond;
        }
        $average = $sum / count($points);

        return new Velocity($average);
    }

    public function getVelocityMax(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $max = max(array_map(fn ($p) => $p['velocity']->metersPerSecond, $points));

        return new Velocity($max);
    }
}
