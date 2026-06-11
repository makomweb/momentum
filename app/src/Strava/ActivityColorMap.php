<?php

declare(strict_types=1);

namespace App\Strava;

final class ActivityColorMap
{
    private const COLORS = [        
        'run' => 'bg-amber-100',
        'walk' => 'bg-green-100', 
        'hike' => 'bg-green-100',
        'ride' => 'bg-red-100',
        'swim' => 'bg-indigo-100',
        'yoga' => 'bg-fuchsia-100',
        'weighttraining' => 'bg-purple-100',
    ];

    public static function getBackgroundClass(string $activityType): string
    {
        return self::COLORS[strtolower($activityType)] ?? self::COLORS['run'];
    }
}
