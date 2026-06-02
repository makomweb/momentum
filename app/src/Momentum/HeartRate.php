<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class HeartRate implements \Stringable
{
    public function __construct(public int $bpm)
    {
    }

    public static function ZERO(): self
    {
        return new self(0);
    }

    public function isValid(): bool
    {
        return $this->bpm > 0;
    }

    public function getZone(HeartRate $max): HeartRateZone
    {
        return HeartRateZone::calculate($this->bpm, $max->bpm);
    }

    public function __toString(): string
    {
        return sprintf('%d bpm', $this->bpm);
    }
}
