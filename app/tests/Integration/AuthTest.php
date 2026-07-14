<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Strava\API\Client;
use App\Strava\API\Token;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthTest extends WebTestCase
{
    #[Test]
    public function download_token(): void
    {
        $client = self::getContainer()->get(Client::class);
        assert($client instanceof Client);

        $code = 'a865b6e8cd18ab8306ccf17bd81a1586bd4fd997';

        $token = $client->requestToken($code);

        self::assertInstanceOf(Token::class, $token);
    }
}
