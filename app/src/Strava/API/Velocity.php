<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Pace;

final readonly class Velocity
{
    public function __construct(public float $metersPerSecond)
    {
    }

    public function asSpeed(): Speed
    {
        return new Speed($this->metersPerSecond, SpeedUnit::METER_PER_SECOND);
    }

    public function asPace(): Pace
    {
        if (0.0 == $this->metersPerSecond) {
            return new Pace(0.0);
        }

        return new Pace(16.667 / $this->metersPerSecond);
    }
}
