<?php

declare(strict_types=1);

namespace App\Momentum;

enum HeartRateZone: string
{
    case ZONE_1 = 'Warm-up/Light';
    case ZONE_2 = 'Easy/Recovery';
    case ZONE_3 = 'Aerobic/Moderate';
    case ZONE_4 = 'Threshold/Hard';
    case ZONE_5 = 'Maximum/Sprinting';

    public static function getZone(int $current, int $max): HeartRateZone
    {
        $percentage = self::getPercentage($current, $max);

        return match (true) {
            $percentage < 60 => HeartRateZone::ZONE_1,
            $percentage < 70 => HeartRateZone::ZONE_2,
            $percentage < 80 => HeartRateZone::ZONE_3,
            $percentage < 90 => HeartRateZone::ZONE_4,
            default => HeartRateZone::ZONE_5,
        };
    }

    private static function getPercentage(int $current, int $max): int
    {
        return (int) (($current / $max) * 100);
    }
}
