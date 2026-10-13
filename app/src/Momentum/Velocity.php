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

    /** @param float[] $values */
    public static function max(array $values): self
    {
        assert(!empty($values));

        return new self(max($values));
    }

    /** @param float[] $values */
    public static function min(array $values): self
    {
        assert(!empty($values));

        return new self(min($values));
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

    public function asSpeed(SpeedUnit $unit = SpeedUnit::KILOMETERS_PER_HOUR): Speed
    {
        return Speed::fromMetersPerSecond($this->metersPerSecond)->as($unit);
    }

    public function asPace(PaceUnit $unit = PaceUnit::MINUTES_PER_KILOMETER): Pace
    {
        return Pace::fromMetersPerSecond($this->metersPerSecond)->as($unit);
    }

    public function asStringable(string $activityType): \Stringable
    {
        return match ($activityType) {
            'Run', 'Walk', 'Hike' => $this->asPace(PaceUnit::MINUTES_PER_KILOMETER),
            'Swim', 'RockClimbing' => $this->asPace(PaceUnit::MINUTES_PER_100_METERS),
            'Ride' => $this->asSpeed(SpeedUnit::KILOMETERS_PER_HOUR),
            default => throw new \InvalidArgumentException(sprintf('Actitiy type "%s" is not supported!', $activityType)),
        };
    }
}
