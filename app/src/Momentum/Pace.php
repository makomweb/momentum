<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Pace implements \Stringable
{
    public function __construct(
        public float $value,
        public PaceUnit $unit = PaceUnit::MINUTES_PER_KILOMETER,
    ) {
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

    public function __toString(): string
    {
        $minutes = (int) $this->value;
        $seconds = (int) (($this->value - $minutes) * 60);

        return sprintf('%d:%02d %s', $minutes, $seconds, $this->unit->value);
    }
}
