<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\Distance;
use App\Strava\API\Velocity;

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

    /**
     * @return array{
     *  elapsed_time: \DateInterval,
     *  distance: Distance,
     *  average_velocity: Velocity
     * }
     */
    public function getData(): array
    {
        return [
            'elapsed_time' => $this->activity->getElapsedTime(),
            'distance' => $this->activity->getDistance(),
            'average_velocity' => $this->activity->getAverageVelocity(),
        ];
    }

    public function getLabel(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => 'Pace',
            'Ride' => 'Speed',
            default => throw new \Exception(sprintf('Unsupported type "%s"!', $type)),
        };
    }

    public function getChartTitle(): string
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike' => '⏱️ Pace Over Time',
            'Swim' => '🏊‍♀️ Speed Over Time',
            'Ride' => '🚴‍♂️ Speed Over Time',
            default => throw new \Exception(sprintf('Unsupported type "%s"!', $type)),
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

    public function getChartYAxisLabel(): string
    {
        return $this->activity->getAverageVelocity()->unit->value;
    }

    public function getElapsedTimeFormatted(): string
    {
        $seconds = $this->getElapsedTimeInSeconds();

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function getElapsedTimeInSeconds(): int
    {
        $dateInterval = $this->activity->getElapsedTime();

        return (int) (
            $dateInterval->d * 86400 +
            $dateInterval->h * 3600 +
            $dateInterval->i * 60 +
            $dateInterval->s
        );
    }
}
