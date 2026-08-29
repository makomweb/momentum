<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Distance implements \Stringable
{
    public function __construct(public float $meters)
    {
    }

    public function __toString(): string
    {
        if ($this->meters < 1000.0) {
            return sprintf('%.1f m', $this->meters);
        }

        return sprintf('%.2f km', $this->meters / 1000);
    }
}
