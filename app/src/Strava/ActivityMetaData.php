<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Speed;
use App\Momentum\SpeedUnit;
use App\Strava\API\Activity;
use App\Strava\API\Distance;

final readonly class ActivityMetaData
{
    public function __construct(private Activity $activity)
    {
    }

    public function getActivityType(): string
    {
        return $this->activity->data['type'];
    }

    public function getActivityName(): string
    {
        return $this->activity->getName();
    }

    /**
     * @return array{
     *  elapsed_time: \DateInterval,
     *  distance: Distance|null,
     *  average_velocity: Speed|Pace|null
     * }
     */
    public function getData(): array
    {
        $distance = $this->activity->data['distance'] > 0
            ? $this->activity->getDistance()
            : null;

        $type = $this->getActivityType();

        $averageVelocity = match ($type) {
            'Run', 'Walk', 'Hike', => $this->activity->getAverageVelocity()->asPace(),
            'Swim' => $this->activity->getAverageVelocity()->asPace()->as(PaceUnit::MINUTES_PER_100_METERS),
            'Ride' => $this->activity->getAverageVelocity()->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR),
            default => null,
        };

        return [
            'elapsed_time' => $this->activity->getElapsedTime(),
            'distance' => $distance,
            'average_velocity' => $averageVelocity,
        ];
    }

    public function getLabel(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => 'Pace',
            'Ride' => 'Speed',
            default => 'Metric',
        };
    }

    public function getVelocityChartTitle(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike' => 'Pace Over Time',
            'Swim' => 'Speed Over Time',
            'Ride' => 'Speed Over Time',
            default => 'Activity Data',
        };
    }

    public function getEmoji(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run' => '🏃‍♂️',
            'Walk' => '🚶‍♂️',
            'Hike' => '🥾',
            'Swim' => '🏊‍♀️',
            'Ride' => '🚴‍♂️',
            default => '🥇',
        };
    }

    public function useInverseScale(): bool
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => true,
            default /* , Ride */ => false,
        };
    }

    public function getChartYAxisMinValue(): int
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run' => 2,
            'Swim' => 1,
            'Walk', 'Hike' => 5,
            default => 0,
        };
    }

    public function getChartYAxisMaxValue(): int
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Swim', 'Walk' => 15,
            'Hike' => 20,
            default => 0,
        };
    }

    public function getChartYAxisLabel(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike' => sprintf('Pace (%s)', PaceUnit::MINUTES_PER_KILOMETER->value),
            'Swim' => sprintf('Pace (%s)', PaceUnit::MINUTES_PER_100_METERS->value),
            default /* , Ride */ => sprintf('Speed (%s)', SpeedUnit::KILOMETERS_PER_HOUR->value),
        };
    }

    public function getMovingTime(): \DateInterval
    {
        return $this->activity->getMovingTime();
    }

    public function getSpeedPaceEmoji(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => '⏱️',
            'Ride' => '🚴‍♂️',
            default => '📊',
        };
    }

    public function usesPaceZones(): bool
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => true,
            default /* Ride */ => false,
        };
    }

    public function isRun(): bool
    {
        return 'Run' === $this->getActivityType();
    }
}
