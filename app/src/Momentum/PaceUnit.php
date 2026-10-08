<?php

declare(strict_types=1);

namespace App\Momentum;

enum PaceUnit: string
{
    case MINUTES_PER_KILOMETER = '/km';
    case MINUTES_PER_100_METERS = '/100m';
}
