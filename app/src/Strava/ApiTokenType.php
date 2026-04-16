<?php

declare(strict_types=1);

namespace App\Strava;

enum ApiTokenType: string
{
    case BEARER = 'Bearer';
}
