<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Distance;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\SpeedUnit;
use App\Strava\API\Activity;

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

    public function getStartDateLocalTime(): \DateTimeImmutable
    {
        return $this->activity->getStartDateLocalTime();
    }

    public function getElapsedTime(): \DateInterval
    {
        return $this->activity->getElapsedTime();
    }

    public function getDistance(): Distance
    {
        return $this->activity->getDistance();
    }

    public function getAverageVelocity(): \Stringable
    {
        return match ($this->getActivityType()) {
            'Run', 'Walk', 'Hike', => $this->activity->getAverageVelocity()->asPace(),
            'Swim' => $this->activity->getAverageVelocity()->asPace()->as(PaceUnit::MINUTES_PER_100_METERS),
            'Ride' => $this->activity->getAverageVelocity()->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR),
            default => Pace::ZERO(),
        };
    }

    public function hasVelocity(): bool
    {
        return $this->activity->hasVelocity();
    }

    public function getLabel(): string
    {
        return match ($this->getActivityType()) {
            'Run', 'Walk', 'Hike', 'Swim' => 'Pace',
            'Ride' => 'Speed',
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $this->getActivityType())),
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

    // TODO Make it dynamic depending on velocity min!
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

    // TODO Make it dynamic depending on velocity max!
    public function getChartYAxisMaxValue(): int
    {
        $type = $this->getActivityType();

        // return match ($type) {
        //     'Run', 'Swim', 'Walk' => 15,
        //     'Hike' => 20,
        //     default => 0,
        // };

        $velocity = $this->activity->getMaxVelocity();

        return match ($type) {
            'Swim' => $velocity->asPace()->as(PaceUnit::MINUTES_PER_100_METERS)->asInt(),
            'Run', 'Walk', 'Hike' => $velocity->asPace()->as(PaceUnit::MINUTES_PER_KILOMETER)->asInt(),
            'Bike' => $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR)->asInt(),
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
