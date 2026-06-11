<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ActivityColorFilter extends AbstractExtension
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
        ];
    }

    private static function getBackgroundClass(string $activityType): string
    {
        return self::COLOR_CLASSES[strtolower($activityType)] ?? self::COLOR_CLASSES['run'];
    }
}
