<?php

declare(strict_types=1);

namespace App\Momentum;

final class WarmupAverage extends Average
{
    public function __construct(\DateTimeImmutable $timestamp)
    {
        parent::__construct($timestamp, 0.0);
    }
}
