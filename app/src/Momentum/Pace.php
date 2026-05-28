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
        return PaceZone::calculate($this, $treshold);
    }

    public function isZero(): bool
    {
        return $this->value < 0.1;
    }

    public function __toString(): string
    {
        $minutes = (int) $this->value;
        $seconds = (int) (($this->value - $minutes) * 60);

        return sprintf('%d:%02d %s', $minutes, $seconds, $this->unit->value);
    }
}
