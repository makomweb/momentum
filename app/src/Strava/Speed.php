<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class Speed implements \Stringable
{
    public function __construct(public float $value, public SpeedUnit $unit)
    {
    }

    public static function fromStrava(float $metersPerSecond, string $type): self
    {
        Assert::that(in_array($type, ['Ride', 'Walk', 'Run', 'WeightTraining']), sprintf('Type %s not yet supported!', $type));

        $speed = new Speed($metersPerSecond, SpeedUnit::METER_PER_SECOND);

        return match ($type) {
            'Ride' => $speed->as(SpeedUnit::KILOMETER_PER_HOUR),
            'Walk', 'Run' => $speed->as(SpeedUnit::MINUTES_PER_KILOMETER),
            default => $speed,
        };
    }

    public function as(SpeedUnit $target): self
    {
        if ($this->unit === $target) {
            return $this;
        }

        if (0.0 == $this->value) {
            return new self(0.0, $target);
        }

        // Normalize to m/s
        $valueMs = match ($this->unit) {
            SpeedUnit::METER_PER_SECOND => $this->value,
            SpeedUnit::KILOMETER_PER_HOUR => $this->value / 3.6,
            SpeedUnit::MINUTES_PER_KILOMETER => 1000 / ($this->value * 60),
        };

        // Convert from m/s into target unit
        $converted = match ($target) {
            SpeedUnit::METER_PER_SECOND => $valueMs,
            SpeedUnit::KILOMETER_PER_HOUR => $valueMs * 3.6,
            SpeedUnit::MINUTES_PER_KILOMETER => (1000 / $valueMs) / 60,
        };

        return new self($converted, $target);
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit->value);
    }
}
