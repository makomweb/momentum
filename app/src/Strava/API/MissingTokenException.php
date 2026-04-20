<?php

declare(strict_types=1);

namespace App\Strava\API;

final class MissingTokenException extends ApiException
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
