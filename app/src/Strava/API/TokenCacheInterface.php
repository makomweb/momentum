<?php

declare(strict_types=1);

namespace App\Strava\API;

interface TokenCacheInterface
{
    public function getToken(int $stravaUserId): Token;

    public function saveToken(Token $token): void;
}
