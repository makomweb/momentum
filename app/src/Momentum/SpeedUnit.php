<?php

declare(strict_types=1);

namespace App\Momentum;

enum SpeedUnit: string
{
    case METER_PER_SECOND = 'm/s';
    case KILOMETER_PER_HOUR = 'km/h';
}
