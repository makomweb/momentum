<?php

declare(strict_types=1);

namespace App\Strava\API;

final class TokenExpiredException extends ApiException
{
    public function __construct(int $stravaUserId)
    {
        parent::__construct(
            sprintf('Token for Strava user "%d" is expired!', $stravaUserId),
            0,
            null
        );
    }
}
