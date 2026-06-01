<?php

declare(strict_types=1);

namespace App\Momentum;

enum PaceZone: string
{
    case ZONE_1 = 'Recovery';
    case ZONE_2 = 'Basis';
    case ZONE_3 = 'Tempo';
    case ZONE_4 = 'Threshold';
    case ZONE_5 = 'vO2max';

    /**
     * Calculate depending on treshold pace.
     */
    public static function calculate(Pace $current, Pace $treshold): self
    {
        $percentage = (int) (($current->value / $treshold->value) * 100);

        return match (true) {
            $percentage > 129 => self::ZONE_1, // more than 129% of the treshold -> Recovery
            $percentage >= 114 => self::ZONE_2, // more than 114% of the treshold -> Aerobic/Basis
            $percentage >= 106 => self::ZONE_3, // more than 106% of the treshold -> Tempo
            $percentage >= 99 => self::ZONE_4, // more than 99% of the treshold -> Treshold
            default => self::ZONE_5, // -> VO2 Max
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::ZONE_1 => '#1e88e5', // Blue - Recovery
            self::ZONE_2 => '#43a047', // Green - Basis/Aerobic
            self::ZONE_3 => '#ffa726', // Orange - Tempo
            self::ZONE_4 => '#e53935', // Red - Threshold
            self::ZONE_5 => '#8e24aa', // Purple - VO2max
        };
    }
}
