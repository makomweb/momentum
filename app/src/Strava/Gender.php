<?php

declare(strict_types=1);

namespace App\Strava;

enum Gender: string
{
    case MALE = 'M';
    case FEMALE = 'F';
    case DIVERSE = 'D';
}
