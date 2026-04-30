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
        $percentage = self::getPercentage($current, $max);

        return match (true) {
            $percentage >= 90 => self::ZONE_5,
            $percentage >= 80 => self::ZONE_4,
            $percentage >= 70 => self::ZONE_3,
            $percentage >= 60 => self::ZONE_2,
            default => self::ZONE_1,
        };
    }

    /**
     * Determine zone depending on functional treshold pace (FTP).
     */
    public static function fromTreshold(Pace $current, Pace $treshold): self
    {
        /*
        Zone 1	Recovery	Slower than 129% of FTP (Time) / <78% (Speed)
        Zone 2	Aerobic Base	114% – 129% of FTP (Time) / 78–88% (Speed)
        Zone 3	Tempo	106% – 113% of FTP (Time) / 88–94% (Speed)
        Zone 4	Threshold	99% – 105% of FTP (Time) / 95–101% (Speed)
        Zone 5	VO2 Max	Faster than 99% of FTP (Time) / >101% (Speed)
        */

        throw new \Exception('Not yet implemented!');
    }

    private static function getPercentage(int $current, int $max): int
    {
        return (int) (($current / $max) * 100);
    }
}
