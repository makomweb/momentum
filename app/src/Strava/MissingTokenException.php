<?php

declare(strict_types=1);

namespace App\Strava;

final class MissingTokenException extends StravaException
{
    public function __construct(int $stravaUserId)
    {
        parent::__construct(
            sprintf('No API token for Strava user with ID "%d"!', $stravaUserId),
            0,
            null
        );
    }
}
