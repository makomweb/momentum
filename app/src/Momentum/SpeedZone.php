<?php

declare(strict_types=1);

namespace App\Momentum;

enum SpeedZone: string
{
    case ZONE_1 = 'Very Slow';
    case ZONE_2 = 'Moderate';
    case ZONE_3 = 'Comfortable';
    case ZONE_4 = 'Fast';
    case ZONE_5 = 'Sprint';

    /**
     * Calculate speed zone based on percentage of average speed.
     *
     * Zone 1 (Very Slow):   < 70% of average
     * Zone 2 (Moderate):    70-90% of average
     * Zone 3 (Comfortable): 90-110% of average
     * Zone 4 (Fast):        110-130% of average
     * Zone 5 (Sprint):      > 130% of average
     */
    public static function calculate(Velocity $current, Velocity $average): self
    {
        if ($average->isZero() || $current->isZero()) {
            return self::ZONE_3;
        }

        $percentage = (int) (($current->metersPerSecond / $average->metersPerSecond) * 100);

        return match (true) {
            $percentage > 130 => self::ZONE_5,       // Sprint
            $percentage >= 110 => self::ZONE_4,      // Fast
            $percentage >= 90 => self::ZONE_3,       // Comfortable
            $percentage >= 70 => self::ZONE_2,       // Moderate
            default => self::ZONE_1,                 // Very Slow
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ZONE_1 => '#1e88e5', // Blue - Very Slow
            self::ZONE_2 => '#43a047', // Green - Moderate
            self::ZONE_3 => '#ffa726', // Orange - Comfortable
            self::ZONE_4 => '#e53935', // Red - Fast
            self::ZONE_5 => '#8e24aa', // Purple - Sprint
        };
    }
}
