<?php

namespace App\Tests\Unit;

use App\Strava\API\Gender;
use App\Strava\API\Token;
use App\Strava\API\TokenType;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
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

        $token = Token::fromData($data);

        self::assertEquals(TokenType::BEARER, $token->tokenType);
        self::assertNotEmpty($token->accessToken);
        self::assertNotEmpty($token->refreshToken);
        self::assertEquals(
            0,
            date_diff(
                \DateTimeImmutable::createFromTimestamp(1776340597),
                $token->expiresAt
            )->days
        );
        self::assertEquals(
            6 * 60 * 60 /* 6 hours) */,
            $token->expiresInSeconds
        );

        $athlete = $token->athlete;

        self::assertEquals(334455, $athlete->id);
        self::assertEquals('Max Mustermann', $athlete->getName());
        self::assertEquals('http://example.com/image.png', $athlete->profile);
        self::assertEquals('http://example.com/image-medium.png', $athlete->profileMedium);
        self::assertEquals(Gender::MALE, $athlete->sex);
    }
}
