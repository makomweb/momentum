<?php

declare(strict_types=1);

namespace App\Momentum;

interface AthleteInterface
{
    public function getStravaId(): int;

    public function getTreshold(): Pace;

    public function getMaxHeartRate(): HeartRate;
}
