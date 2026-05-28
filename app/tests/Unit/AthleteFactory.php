<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\AthleteInterface;
use App\Momentum\Pace;
use App\Strava\API\HeartRate;

trait AthleteFactory
{
    protected static function createAthlete(Pace $threshold, HeartRate $maxHeartRate): AthleteInterface
    {
        return new class($threshold, $maxHeartRate) implements AthleteInterface {
            public function __construct(private Pace $threshold, private HeartRate $maxHeartRate)
            {
            }

            public function getTreshold(): Pace
            {
                return $this->threshold;
            }

            public function getMaxHeartRate(): HeartRate
            {
                return $this->maxHeartRate;
            }
        };
    }
}
