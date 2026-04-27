<?php

declare(strict_types=1);

namespace App\Momentum;

use DateTimeImmutable;

final readonly class Period
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public Zone $zone,
    ) {
    }
}
