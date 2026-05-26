<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Assert\Assert;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class Configuration
{
    public function __construct(
        #[Autowire('%strava_client_id%')]
        public string $clientId,
        #[Autowire('%strava_client_secret%')]
        public string $clientSecret,
        #[Autowire('%strava_redirect_uri%')]
        private string $redirectUri,
    ) {
    }

    public function getAuthorizationAddress(string $state = ''): string
    {
        Assert::that(!empty($this->clientId), 'Env var "STRAVA_CLIENT_ID" must be set!');
        Assert::that(!empty($this->redirectUri), 'Env var "STRAVA_REDIRECT_URI" must be set!');

        $url = sprintf(
            'https://www.strava.com/oauth/authorize?client_id=%s&response_type=code&redirect_uri=%s&scope=activity:read_all',
            $this->clientId,
            urlencode($this->redirectUri)
        );

        if ($state) {
            $url .= '&state='.urlencode($state);
        }

        return $url;
    }
}
