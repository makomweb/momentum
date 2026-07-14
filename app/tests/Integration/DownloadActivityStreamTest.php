<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Strava\API\Client;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DownloadActivityStreamTest extends WebTestCase
{
    #[Test]
    public function client_service_is_available(): void
    {
        $container = self::getContainer();

        // Verify that the Client service is properly registered and available
        self::assertTrue($container->has(Client::class));

        $client = $container->get(Client::class);
        self::assertInstanceOf(Client::class, $client);
    }
}

