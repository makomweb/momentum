<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Distance;
use App\Momentum\Velocity;
use App\Strava\API\Activity;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class ActivityMetaData
{
    public function __construct(
        private Activity $activity,
        private TranslatorInterface $translator,
    ) {
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
        return $this->activity->getAverageVelocityAsStringable();
    }

    public function getMaxVelocity(): Velocity
    {
        return $this->activity->getMaxVelocity();
    }

    public function hasVelocity(): bool
    {
        return $this->activity->hasVelocity();
    }

    public function getLabel(): string
    {
        $type = $this->getActivityType();

        $key = match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => 'activity.label_pace',
            'Ride', 'Swim' => 'activity.label_speed',
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $this->getActivityType())),
        };

        return $this->translator->trans($key);
    }

    public function getVelocityChartTitle(): string
    {
        $type = $this->getActivityType();

        $key = match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => 'activity.pace_over_time',
            'Swim', 'Ride' => 'activity.speed_over_time',
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $this->getActivityType())),
        };

        return $this->translator->trans($key);
    }

    public function getVelocityLabel(): string
    {
        $type = $this->getActivityType();

        $key = match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => 'activity.pace',
            'Swim', 'Ride' => 'activity.speed',
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $this->getActivityType())),
        };

        return $this->translator->trans($key);
    }

    public function getHeartRateLabel(): string
    {
        $type = $this->getActivityType();

        $key = match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => 'activity.pace',
            'Swim', 'Ride' => 'activity.speed',
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $this->getActivityType())),
        };

        return $this->translator->trans($key);
    }

    public function hasSpeed(): bool
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim', 'RockClimbing' => false,
            default /* , Ride */ => true,
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
            'Run', 'Walk', 'Hike', 'Swim', 'RockClimbing' => '⏱️',
            'Ride' => '🚴‍♂️',
            default => '📊',
        };
    }

    // TODO: Unify with hasSpeed()!
    public function usesPaceZones(): bool
    {
        $type = $this->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim', 'RockClimbing' => true,
            default /* Ride */ => false,
        };
    }

    public function isRun(): bool
    {
        return 'Run' === $this->getActivityType();
    }
}
