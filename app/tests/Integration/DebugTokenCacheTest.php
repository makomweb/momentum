<?php

namespace App\Tests\Integration;

use App\Strava\API\Gender;
use App\Strava\API\Token;
use App\Strava\API\TokenCacheInterface;
use App\Strava\API\TokenType;
use App\Strava\DebugTokenCache;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DebugTokenCacheTest extends KernelTestCase
{
    #[Test]
    public function persist_and_restore_should_work(): void
    {
        $cache = self::getContainer()->get(TokenCacheInterface::class);

        assert($cache instanceof DebugTokenCache);

        $token = Token::from([
            'token_type' => TokenType::BEARER->value,
            'expires_at' => new \DateTimeImmutable()->getTimestamp(),
            'expires_in' => 30,
            'access_token' => 'my_access_token',
            'refresh_token' => 'my_refresh_token',
            'athlete' => [
                'id' => 123456789,
                'username' => 'Power-Athlete',
                'firstname' => 'Maxi',
                'lastname' => 'Mustermann',
                'sex' => Gender::DIVERSE->value,
                'premium' => false,
                'profile_medium' => 'empty',
                'profile' => 'empty',
            ],
        ]);

        $cache->saveToken($token);

        $restored = $cache->getToken(123456789);

        self::assertInstanceOf(Token::class, $restored);
        self::assertEquals('my_access_token', $token->accessToken);
    }
}
