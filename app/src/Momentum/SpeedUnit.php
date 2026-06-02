<?php

declare(strict_types=1);

namespace App\Momentum;

enum SpeedUnit: string
{
    case METERS_PER_SECOND = 'm/s';
    case KILOMETERS_PER_HOUR = 'km/h';
}
