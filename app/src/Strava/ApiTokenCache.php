<?php

declare(strict_types=1);

namespace App\Strava;

use Psr\Cache\CacheItemPoolInterface;

final readonly class ApiTokenCache
{
    public function __construct(private CacheItemPoolInterface $pool)
    {
    }

    public function getToken(int $stravaUserId): ApiToken
    {
        $item = $this->pool->getItem(strval($stravaUserId));
        assert($item->isHit(), sprintf('No API token for %d', $stravaUserId));
        $value = $item->get();
        assert($value instanceof ApiToken);

        return $value;
    }

    public function saveToken(ApiToken $token): void
    {
        $item = $this->pool->getItem(strval($token->getAthlete()->getId()));
        $item->set($token);
        $this->pool->save($item);
    }
}
