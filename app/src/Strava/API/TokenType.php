<?php

declare(strict_types=1);

namespace App\Strava\API;

enum TokenType: string
{
    case BEARER = 'Bearer';
}
