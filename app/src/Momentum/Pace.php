<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Pace implements \Stringable
{
    public function __construct(
        public float $value,
        public PaceUnit $unit,
    ) {
    }

    public static function fromSecondsPerKilometer(int $secondsPerKilometer): self
    {
        return $secondsPerKilometer > 0
            ? new self($secondsPerKilometer / 60, PaceUnit::MINUTES_PER_KILOMETER)
            : Pace::ZERO();
    }

    public static function fromMetersPerSecond(float $metersPerSecond): self
    {
        return $metersPerSecond < 0.1
            ? self::ZERO()
            : new self(16.667 / $metersPerSecond, PaceUnit::MINUTES_PER_KILOMETER);
    }

    public static function ZERO(): self
    {
        return new self(0.0, PaceUnit::MINUTES_PER_KILOMETER);
    }

    public function getZone(Pace $treshold): PaceZone
    {
        return $this->isZero()
            ? PaceZone::ZONE_1
            : PaceZone::calculate($this, $treshold);
    }

    public function isZero(): bool
    {
        return $this->value < 0.1;
    }

    public function as(PaceUnit $unit): self
    {
        return match (true) {
            ($this->unit == $unit) => $this,
            PaceUnit::MINUTES_PER_100_METERS == $unit => new self($this->value / 10, $unit),
            PaceUnit::MINUTES_PER_KILOMETER == $unit => new self($this->value * 10, $unit),
        };
    }

    public function multiply(float $factor): self
    {
        return new self($this->value * $factor, $this->unit);
    }

    public function __toString(): string
    {
        $minutes = (int) $this->value;
        $seconds = (int) (($this->value - $minutes) * 60);

        return sprintf('%d:%02d %s', $minutes, $seconds, $this->unit->value);
    }
}
