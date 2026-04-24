<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class Pace implements \Stringable
{
    public function __construct(public float $value, public PaceUnit $unit = PaceUnit::MINUTES_PER_KILOMETER)
    {
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit->value);
    }
}
