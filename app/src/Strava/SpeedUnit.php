<?php

declare(strict_types=1);

namespace App\Strava;

enum SpeedUnit: string
{
    case METER_PER_SECOND = 'm/s';
    case KILOMETER_PER_HOUR = 'km/h';
    case MINUTES_PER_KILOMETER = 'min/km';
}
