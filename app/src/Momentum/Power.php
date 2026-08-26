<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Power implements \Stringable
{
    public function __construct(public int $watt)
    {
    }

    public static function ZERO(): self
    {
        return new self(0);
    }

    public function isZero(): bool
    {
        return $this->watt < 1;
    }

    public function __toString(): string
    {
        return sprintf('%d W', $this->watt);
    }
}
