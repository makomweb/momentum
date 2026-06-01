<?php

declare(strict_types=1);

namespace App\Twig;

use App\Momentum\SpeedUnit;
use App\Momentum\Velocity;
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
            'Ride' => (string) $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR),
            default => '',
        };
    }
}
