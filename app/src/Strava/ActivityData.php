<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\ActivityDataPoint;
use App\Momentum\PaceUnit;
use App\Momentum\SpeedUnit;
use App\Momentum\SpeedZone;
use App\Momentum\Velocity;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;

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
     * @return ActivityDataPoint[]
     */
    public function getDataPoints(): array
    {
        return iterator_to_array($this->yieldDataPoints());
    }

    /**
     * @return \Generator<ActivityDataPoint>
     */
    private function yieldDataPoints(): \Generator
    {
        yield from $this->stream->yield($this->activity->getStartDateLocalTime(), self::MOVING_AVERAGE_PERIOD);
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
        foreach ($this->yieldDataPoints() as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->hasVelocity()
                    ? $this->convertVelocity($point->getVelocity())
                    : 0.0,
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
        foreach ($this->yieldDataPoints() as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->hasVelocity()
                    ? $this->convertVelocity($point->getVelocityAverage())
                    : 0.0,
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
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->getHeartRate()->bpm,
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
            $velocity = $point->getVelocityAverage();
            if ($velocity->isZero()) {
                continue;
            }

            $sum += $velocity->metersPerSecond;
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
            $velocity = $point->getVelocityAverage();
            if (!$velocity->isZero()) {
                $velocities[] = $velocity->metersPerSecond;
            }
        }

        return Velocity::max($velocities);
    }

    /**
     * @return array<array{timestamp: string, value: string}>
     */
    public function getSpeedZonePoints(): array
    {
        return iterator_to_array($this->yieldSpeedZonePoints());
    }

    /**
     * @return \Generator<array{timestamp: string, value: string}>
     */
    private function yieldSpeedZonePoints(): \Generator
    {
        assert(!$this->getVelocityAverage()->isZero());

        $average = $this->getVelocityAverage();
        foreach ($this->yieldDataPoints() as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->hasVelocity()
                    ? $point->getSpeedZone($average)->value
                    : '',
            ];
        }
    }

    /**
     * Get speed zone statistics for bikes.
     *
     * @return array<string, array{duration: int, duration_percentage: float, zone: SpeedZone}>
     */
    public function getSpeedZoneStatistics(): array
    {
        $stats = [];
        $average = $this->getVelocityAverage();
        $totalPoints = 0;

        foreach ($this->yieldDataPoints() as $point) {
            if (!$point->hasVelocity()) {
                continue;
            }

            ++$totalPoints;
            $zone = $point->getSpeedZone($average);
            $zoneKey = $zone->value;

            if (!isset($stats[$zoneKey])) {
                $stats[$zoneKey] = [
                    'zone' => $zone,
                    'duration' => 0,
                    'duration_percentage' => 0.0,
                ];
            }

            ++$stats[$zoneKey]['duration'];
        }

        if (0 === $totalPoints) {
            return [];
        }

        foreach ($stats as &$stat) {
            $stat['duration_percentage'] = ($stat['duration'] / $totalPoints) * 100;
        }

        return $stats;
    }

    private function convertVelocity(Velocity $velocity): float
    {
        $type = $this->activity->data['type'];

        return match ($type) {
            'Run', 'Walk', 'Hike' => $velocity->asPace()->value,
            'Swim' => $velocity->asPace()->as(PaceUnit::MINUTES_PER_100_METERS)->value,
            'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR)->value,
            default => 0.0,
        };
    }
}
