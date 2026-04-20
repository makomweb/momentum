<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class StravaDetails
{
    public function __construct(
        #[Autowire('%strava_client_id%')]
        public string $clientId,
        #[Autowire('%strava_client_secret%')]
        public string $clientSecret,
    ) {
    }
}
