<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Velocity
{
    public function __construct(public float $metersPerSecond = 0)
    {
    }

    public static function ZERO(): self
    {
        return new self(0.0);
    }

    /** @param float[] $velocities */
    public static function max(array $velocities): self
    {
        return empty($velocities)
            ? self::ZERO()
            : new self(max($velocities));
    }

    public static function create(Pace $pace): self
    {
        return $pace->isZero()
            ? self::ZERO()
            : new self(16.667 / $pace->value);
    }

    public function isZero(): bool
    {
        return 0.0 == $this->metersPerSecond;
    }

    public function asSpeed(): Speed
    {
        return Speed::fromMetersPerSecond($this->metersPerSecond);
    }

    public function asPace(): Pace
    {
        return Pace::fromMetersPerSecond($this->metersPerSecond);
    }

    public function asStringable(string $activityType): \Stringable
    {
        return match ($activityType) {
            'Run', 'Walk', 'Hike' => $this->asPace(),
            'Swim' => $this->asPace()->as(PaceUnit::PER_100_METERS),
            'Ride' => $this->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR),
            default => throw new \Exception(sprintf('Unsupported activity type "%s"!', $activityType)),
        };
    }
}
