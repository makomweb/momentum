<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Velocity
{
    public function __construct(public float $metersPerSecond = 0)
    {
    }

    /** @param float[] $velocities */
    public static function max(array $velocities): self
    {
        $max = !empty($velocities) ? max($velocities) : 0;

        return new self($max);
    }

    public static function create(Pace $pace): self
    {
        if ($pace->isZero()) {
            return new self(0.0);
        }

        return new self(16.667 / $pace->value);
    }

    public function isZero(): bool
    {
        return $this->asPace()->isZero();
    }

    public function tooSlow(): bool
    {
        return $this->asPace()->value > 60.0; // > 60 min/km
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
