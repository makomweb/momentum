<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Strava\API\HeartRate;

interface AthleteInterface
{
    public function getTreshold(): Pace;

    public function getMaxHeartRate(): HeartRate;
}
