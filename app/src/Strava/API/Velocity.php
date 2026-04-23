<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class Velocity implements \Stringable
{
    public function __construct(public float $value, public VelocityUnit $unit)
    {
    }

    public static function fromStrava(float $metersPerSecond, string $type): self
    {
        $velocity = new self($metersPerSecond, VelocityUnit::METER_PER_SECOND);

        return match ($type) {
            'Ride' => $velocity->as(VelocityUnit::KILOMETER_PER_HOUR),
            'Walk', 'Run', 'Hike' => $velocity->as(VelocityUnit::MINUTES_PER_KILOMETER),
            default => $velocity, // Default to m/s for unknown types
        };
    }

    public function as(VelocityUnit $target): self
    {
        if ($this->unit === $target) {
            return $this;
        }

        if (0.0 == $this->value) {
            return new self(0.0, $target);
        }

        // Normalize to m/s
        $valueMs = match ($this->unit) {
            VelocityUnit::METER_PER_SECOND => $this->value,
            VelocityUnit::KILOMETER_PER_HOUR => $this->value / 3.6,
            VelocityUnit::MINUTES_PER_KILOMETER => 1000 / ($this->value * 60),
        };

        // Convert from m/s into target unit
        $converted = match ($target) {
            VelocityUnit::METER_PER_SECOND => $valueMs,
            VelocityUnit::KILOMETER_PER_HOUR => $valueMs * 3.6,
            VelocityUnit::MINUTES_PER_KILOMETER => (1000 / $valueMs) / 60,
        };

        return new self($converted, $target);
    }

    public function __toString(): string
    {
        return sprintf('%.2f %s', $this->value, $this->unit->value);
    }
}
