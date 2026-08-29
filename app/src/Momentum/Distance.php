<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Distance implements \Stringable
{
    public function __construct(public float $meters)
    {
    }

    public static function ZERO(): self
    {
        return new self(0.0);
    }

    public function isZero(): bool
    {
        return $this->meters < 0.1;
    }

    public function __toString(): string
    {
        if ($this->meters < 1000.0) {
            return sprintf('%.0f m', $this->meters);
        }

        return sprintf('%.1f km', $this->meters / 1000);
    }
}
