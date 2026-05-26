<?php

declare(strict_types=1);

namespace App\Twig;

use App\Strava\API\SpeedUnit;
use App\Strava\API\Velocity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class VelocityExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_velocity', self::formatVelocity(...)),
        ];
    }

    public static function formatVelocity(Velocity $velocity, string $activityType): string
    {
        return match ($activityType) {
            'Run', 'Walk', 'Hike', 'Swim' => (string) $velocity->asPace(),
            'Ride' => (string) $velocity->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR),
            default => '',
        };
    }
}
