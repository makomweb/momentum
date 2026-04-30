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

    /**
     * Calculate depending on max. HF.
     */
    public static function calculate(int $current, int $max): self
    {
        $percentage = (int) (($current / $max) * 100);

        return match (true) {
            $percentage >= 90 => self::ZONE_5,
            $percentage >= 80 => self::ZONE_4,
            $percentage >= 70 => self::ZONE_3,
            $percentage >= 60 => self::ZONE_2,
            default => self::ZONE_1,
        };
    }
}
