<?php

declare(strict_types=1);

namespace App\Strava\API;

enum PaceUnit: string
{
    case MINUTES_PER_KILOMETER = 'min/km';
}
