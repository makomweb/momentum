<?php

declare(strict_types=1);

namespace App\RunForge;

use DateTimeImmutable;

final class InitialAverage extends Average
{
    private function __construct(
        public DateTimeImmutable $timestamp,
        int|float $initial
    ) {
        parent::__construct(0, $initial);
    }

    public function create(): self
    {
        return new self(new \DateTimeImmutable(), 0);
    }

    public function __toString(): string
    {
        return $this->timestamp->format(\DateTimeInterface::RFC2822);
    }
}
