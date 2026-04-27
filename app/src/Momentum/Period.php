<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Period
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public Zone $zone,
    ) {
    }
}
