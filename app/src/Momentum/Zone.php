<?php

declare(strict_types=1);

namespace App\Momentum;

enum Zone: string
{
    case ZONE_1 = 'Warm-up/Light';
    case ZONE_2 = 'Easy/Recovery';
    case ZONE_3 = 'Aerobic/Moderate';
    case ZONE_4 = 'Threshold/Hard';
    case ZONE_5 = 'Maximum/Sprinting';

    /**
     * Determine zone depending on HF.
     */
    public static function fromHeartFrequency(int $current, int $max): self
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

    /**
     * Determine zone depending on FTP (functional treshold pace).
     */
    public static function fromTreshold(Pace $current, Pace $treshold): self
    {
        $percentage = (int) (($current->value / $treshold->value) * 100);

        return match (true) {
            $percentage > 129 => self::ZONE_1, // more than 129% of the FTP -> Recovery
            $percentage >= 114 => self::ZONE_2, // more than 114% of the FTP -> Aerobic/Basis
            $percentage >= 106 => self::ZONE_3, // more than 106% of the FTP -> Tempo
            $percentage >= 99 => self::ZONE_4, // more than 99% of the FTP -> Treshold
            default => self::ZONE_5, // -> VO2 Max
        };
    }
}
