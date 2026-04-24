<?php

declare(strict_types=1);

namespace App\Strava\API;

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
        return new Pace((1000 / $this->metersPerSecond) / 60);
    }
}
