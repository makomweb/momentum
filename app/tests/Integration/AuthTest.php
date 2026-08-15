<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Strava\API\Client;
use App\Strava\API\MissingTokenException;
use App\Strava\API\TokenCacheInterface;
use App\Strava\DebugTokenCache;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    private const STRAVA_USER_ID = 14417573;

    public function setUp(): void
    {
        self::bootKernel();

        $cache = self::getContainer()->get(TokenCacheInterface::class);
        assert($cache instanceof DebugTokenCache);

        $cache->removeToken(self::STRAVA_USER_ID);
    }

    #[Test]
    public function refresh_token_should_fail(): void
    {
        $client = self::getContainer()->get(Client::class);
        assert($client instanceof Client);

        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessageIs(sprintf('No API token for Strava user with ID "%s"!', self::STRAVA_USER_ID));
        $token = $client->refreshTokenFor(self::STRAVA_USER_ID);
    }
}
