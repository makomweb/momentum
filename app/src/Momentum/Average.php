<?php

declare(strict_types=1);

namespace App\Momentum;

abstract class Average implements \Stringable
{
    protected function __construct(
        public \DateTimeImmutable $timestamp,
        public float $value,
    ) {
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: AVG: %.2F',
            $this->timestamp->format(\DateTimeInterface::RFC2822),
            $this->value,
        );
    }
}
