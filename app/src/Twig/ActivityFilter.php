<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Activity;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Speed;
use App\Momentum\SpeedUnit;
use App\Strava\ActivityData;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ActivityFilter extends AbstractExtension
{
    private const PACE_RANGE_PADDING = 0.1;

    private const COLOR_CLASSES = [
        'run' => 'bg-amber-100',
        'walk' => 'bg-green-100',
        'hike' => 'bg-green-100',
        'ride' => 'bg-red-100',
        'swim' => 'bg-indigo-100',
        'yoga' => 'bg-fuchsia-100',
        'weighttraining' => 'bg-purple-100',
    ];

    public function __construct(private TranslatorInterface $translator)
    {
    }

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
            new TwigFilter('x_axis_label', self::getChartXAxisLabel(...)),
            new TwigFilter('y_axis_hf_label', self::getChartYAxisHeartRateLabel(...)),
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
            'Walk' => '🚶🏽‍♀️',
            'Hike' => '🥾',
            'Swim' => '🏊‍♀️',
            'Ride' => '🚴‍♂️',
            'Yoga' => '🧘🏻‍♀️',
            default => '🥇',
        };
    }

    private static function getMaxVelocity(ActivityData $activity): float
    {
        $metaData = $activity->getMetaData();

        if ($metaData->hasSpeed()) {
            return $metaData->getMaxVelocity()->asSpeed()->multiply(1.1 /* add 10 % */)->value;
        }

        $type = $metaData->getActivityType();

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim', 'RockClimbing' => self::getVelocityBounds($activity)['max'],
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

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim', 'RockClimbing' => self::getVelocityBounds($activity)['min'],
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        };
    }

    public function getChartYAxisLabel(ActivityData $activity): string
    {
        $type = $activity->getMetaData()->getActivityType();

        $data = match ($type) {
            'Run', 'Walk', 'Hike', 'RockClimbing' => [
                'name' => $this->translator->trans('activity.label_pace'),
                'unit' => PaceUnit::MINUTES_PER_KILOMETER->value,
            ],
            'Swim' => [
                'name' => $this->translator->trans('activity.label_pace'),
                'unit' => PaceUnit::MINUTES_PER_100_METERS->value,
            ],
            'Ride' => [
                'name' => $this->translator->trans('activity.label_speed'),
                'unit' => SpeedUnit::KILOMETERS_PER_HOUR->value,
            ],
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $type)),
        };

        return $this->translator->trans('activity.velocity_diagram_y_axis_label', $data);
    }

    public function getChartXAxisLabel(ActivityData $activity): string
    {
        return $this->translator->trans('activity.diagram_x_axis_label');
    }

    public function getChartYAxisHeartRateLabel(ActivityData $activity): string
    {
        return $this->translator->trans('activity.heart_rate_diagram_y_axis_label');
    }

    /**
     * @return array{min: float, max: float}
     */
    private static function getVelocityBounds(ActivityData $activity): array
    {
        $values = self::getVelocityAverageValues($activity);

        if ([] === $values) {
            return [
                'min' => 0.0,
                'max' => 0.0,
            ];
        }

        $min = min($values);
        $max = max($values);
        $padding = ($max - $min) * self::PACE_RANGE_PADDING;

        return [
            'min' => max(0.0, $min - $padding),
            'max' => $max + $padding,
        ];
    }

    /**
     * @return list<float>
     */
    private static function getVelocityAverageValues(ActivityData $activity): array
    {
        return array_values(array_filter(
            array_column($activity->getVelocityAveragePoints(), 'value'),
            static fn (float $value): bool => $value > 0.0,
        ));
    }
}
