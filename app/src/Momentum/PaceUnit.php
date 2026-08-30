<?php

declare(strict_types=1);

namespace App\Momentum;

enum PaceUnit: string
{
    case PER_KILOMETER = '/km';
    case PER_100_METERS = '/100m';
}
