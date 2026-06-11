<?php

declare(strict_types=1);

namespace App\Twig;

use App\Strava\ActivityColorMap;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class ActivityColorFilter extends AbstractExtension
{
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
        return ActivityColorMap::getBackgroundClass($activityType);
    }
}
