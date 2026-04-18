<?php

namespace App\Tests\Unit;

use App\Strava\ApiToken;
use App\Strava\ApiTokenType;
use App\Strava\Gender;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ApiTokenTest extends TestCase
{
    public function test_deserialize(): void
    {
        $data = [
            'token_type' => 'Bearer',
            'refresh_token' => 'ABC',
            'access_token' => 'XYZ',
            'expires_at' => 1776340597,
            'expires_in' => 21600,
            'athlete' => [
                'id' => 334455,
                'username' => 'test-user-abc',
                'firstname' => 'Max',
                'lastname' => 'Mustermann',
                'sex' => 'M',
                'premium' => false,
                'profile_medium' => 'http://example.com/image-medium.png',
                'profile' => 'http://example.com/image.png',
            ],
        ];

        $token = new ApiToken($data);

        self::assertEquals(ApiTokenType::BEARER, $token->getTokenType());
        self::assertNotEmpty($token->getAccessToken());
        self::assertNotEmpty($token->getRefreshToken());
        self::assertEquals(
            0,
            date_diff(
                DateTimeImmutable::createFromTimestamp(1776340597),
                $token->getExpiresAt()
            )->days
        );
        self::assertEquals(
            6 * 60 * 60 /* 6 hours) */,
            $token->getExpiresIn()
        );

        $athlete = $token->getAthlete();

        self::assertEquals(334455, $athlete->getId());
        self::assertEquals('Max Mustermann', $athlete->getName());
        self::assertEquals('http://example.com/image.png', $athlete->getProfile());
        self::assertEquals('http://example.com/image-medium.png', $athlete->getProfileMedium());
        self::assertEquals(Gender::MALE, $athlete->getGender());
    }
}
