<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Activity;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Speed;
use App\Momentum\SpeedUnit;
use App\Momentum\Velocity;
use App\Strava\ActivityData;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ActivityFilter extends AbstractExtension
{
    private const COLOR_CLASSES = [
        'run' => 'bg-amber-100',
        'walk' => 'bg-green-100',
        'hike' => 'bg-green-100',
        'ride' => 'bg-red-100',
        'swim' => 'bg-indigo-100',
        'yoga' => 'bg-fuchsia-100',
        'weighttraining' => 'bg-purple-100',
    ];

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('activity_bg_color', self::getBackgroundClass(...)),
            new TwigFilter('activity_emoji', self::getEmoji(...)),
            new TwigFilter('y_axis_max', self::getMaxVelocity(...)),
            new TwigFilter('y_axis_min', self::getMinVelocity(...)),
            new TwigFilter('y_axis_label', self::getChartYAxisLabel(...)),
        ];
    }

    private static function getBackgroundClass(string $activityType): string
    {
        return self::COLOR_CLASSES[strtolower($activityType)] ?? self::COLOR_CLASSES['run'];
    }

    private static function getEmoji(Activity $activity): string
    {
        $type = $activity->getData()['type'];

        return match ($type) {
            'Run' => '🏃‍♂️',
            'RockClimbing' => '🧗🏻‍♂️',
            'Walk' => '🚶‍♂️',
            'Hike' => '🥾',
            'Swim' => '🏊‍♀️',
            'Ride' => '🚴‍♂️',
            default => '🥇',
        };
    }

    private static function getMaxVelocity(ActivityData $activity): float
    {
        $metaData = $activity->getMetaData();
        $maxVelocity = $metaData->getMaxVelocity();

        if ($metaData->hasSpeed()) {
            return $maxVelocity->asSpeed()->multiply(1.1 /* add 10 % */)->value;
        }

        $type = $metaData->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike' => $maxVelocity->asPace(PaceUnit::MINUTES_PER_KILOMETER)->multiply(1.1 /* add 10 % */)->value,
            'Swim', 'RockClimbing' => $maxVelocity->asPace(PaceUnit::MINUTES_PER_100_METERS)->multiply(1.1 /* add 10 % */)->value,
            'Yoga' => Pace::ZERO()->value,
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        };
    }

    public static function getMinVelocity(ActivityData $activity): float
    {
        if ($activity->getMetaData()->hasSpeed()) {
            return Speed::ZERO()->value;
        }

        $type = $activity->getMetaData()->getActivityType();

        // return match ($type) {
        //     'Run', 'Swim', 'Walk' => 15.0,
        //     'Hike' => 40.0,
        //     default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        // };

        $values = array_map(
            static fn (array $p) => $p['value'],
            $activity->getVelocityRawPoints()
        );

        $min = Velocity::min($values);
        $max = Velocity::max($values);

        $type = $activity->getMetaData()->getActivityType();

        dump([
            'method' => __METHOD__,
            'type' => $type,
            'min' => $min,
            'max' => $max,
            'min_pace' => (string) $min->asPace(PaceUnit::MINUTES_PER_KILOMETER),
            'max_pace' => (string) $max->asPace(PaceUnit::MINUTES_PER_KILOMETER),
        ]);
        
        return match ($type) {
            'Run', 'Walk', 'Hike' => $min->asPace(PaceUnit::MINUTES_PER_KILOMETER)->value,
            'Swim', 'RockClimbing' => $min->asPace(PaceUnit::MINUTES_PER_100_METERS)->value,
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        };
    }

    public function getChartYAxisLabel(ActivityData $activity): string
    {
        $type = $activity->getMetaData()->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => sprintf('Pace (%s)', PaceUnit::MINUTES_PER_KILOMETER->value),
            'Swim' => sprintf('Pace (%s)', PaceUnit::MINUTES_PER_100_METERS->value),
            'Ride' => sprintf('Speed (%s)', SpeedUnit::KILOMETERS_PER_HOUR->value),
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        };
    }
}
