<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Speed
{
    public function __construct(public float $value, public SpeedUnit $unit)
    {
    }

    public static function fromMetersPerSecond(float $metersPerSecond): self
    {
        return $metersPerSecond < 0.1
            ? self::ZERO()
            : new self($metersPerSecond, SpeedUnit::METERS_PER_SECOND);
    }

    public static function ZERO(): self
    {
        return new self(0.0, SpeedUnit::METERS_PER_SECOND);
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
            SpeedUnit::METERS_PER_SECOND => new self($this->value / 3.6, SpeedUnit::METERS_PER_SECOND),
            SpeedUnit::KILOMETERS_PER_HOUR => new self($this->value * 3.6, SpeedUnit::KILOMETERS_PER_HOUR),
        };
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit->value);
    }
}
