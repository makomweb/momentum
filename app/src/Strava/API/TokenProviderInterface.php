<?php

declare(strict_types=1);

namespace App\Strava\API;

interface TokenProviderInterface
{
    public function getToken(int $stravaUserId): Token;
}
