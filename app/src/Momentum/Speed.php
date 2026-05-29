<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Speed
{
    public function __construct(public float $value, public SpeedUnit $unit)
    {
    }

    public function as(SpeedUnit $target): self
    {
        if ($this->unit === $target) {
            return $this;
        }

        if (0.0 == $this->value) {
            return new self(0.0, $target);
        }

        return match ($target) {
            SpeedUnit::METER_PER_SECOND => new Speed($this->value / 3.6, SpeedUnit::METER_PER_SECOND),
            SpeedUnit::KILOMETER_PER_HOUR => new Speed($this->value * 3.6, SpeedUnit::KILOMETER_PER_HOUR),
        };
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit->value);
    }
}
