<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Assert;
use App\Strava\API\MissingTokenException;
use App\Strava\API\Token;
use App\Strava\API\TokenProviderInterface;
use Psr\Cache\CacheItemPoolInterface;

final readonly class TokenCache implements TokenProviderInterface
{
    public function __construct(private CacheItemPoolInterface $pool)
    {
    }

    public function getToken(int $stravaUserId): Token
    {
        $item = $this->pool->getItem(strval($stravaUserId));
        Assert::that(
            $item->isHit(),
            new MissingTokenException($stravaUserId)
        );
        $value = $item->get();
        assert($value instanceof Token);

        return $value;
    }

    public function saveToken(Token $token): void
    {
        $item = $this->pool->getItem(strval($token->getAthlete()->getId()));
        $item->set($token);
        $this->pool->save($item);
    }
}
