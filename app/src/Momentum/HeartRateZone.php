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

    public function getColor(): string
    {
        return match ($this) {
            self::ZONE_1 => '#1e88e5', // Blue - Warm-up/Light
            self::ZONE_2 => '#43a047', // Green - Easy/Recovery
            self::ZONE_3 => '#ffa726', // Orange - Aerobic/Moderate
            self::ZONE_4 => '#e53935', // Red - Threshold/Hard
            self::ZONE_5 => '#8e24aa', // Purple - Maximum/Sprinting
        };
    }

    public function confirms(PaceZone $paceZone): bool
    {
        return match (true) {
            // Hard pace zones (Threshold, VO2max) need hard HR zones (Threshold, Maximum)
            in_array($paceZone, [PaceZone::ZONE_4, PaceZone::ZONE_5]) => in_array($this, [self::ZONE_4, self::ZONE_5]),

            // Tempo pace zone accepts HR zones 3-4 (Aerobic/Moderate to Threshold/Hard)
            PaceZone::ZONE_3 === $paceZone => in_array($this, [self::ZONE_3, self::ZONE_4]),

            // Easy/Recovery pace zones are always valid
            default => true,
        };
    }
}
