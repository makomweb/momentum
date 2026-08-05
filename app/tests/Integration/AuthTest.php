<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Strava\API\Client;
use App\Strava\API\MissingTokenException;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    #[Test]
    public function refresh_token_should_work(): void
    {
        $client = self::getContainer()->get(Client::class);
        assert($client instanceof Client);

        $this->expectException(MissingTokenException::class);
        $this->expectExceptionMessageIs('No API token for Strava user with ID "14417573"!');
        $token = $client->refreshTokenFor(14417573);
    }
}
