<?php

declare(strict_types=1);

namespace App\Strava\API;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ActivityFetcher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private TokenProviderInterface $tokenProvider,
    ) {
    }

    /** @return Activity[] */
    public function fetch(int $stravaUserId, int $page = 1, int $perPage = 5): array
    {
        $token = $this->tokenProvider->getToken($stravaUserId);

        Assert::that(!$token->isExpired(), 'Token is expired!');

        $response = $this->httpClient->request('GET', 'https://www.strava.com/api/v3/athlete/activities', [
            'headers' => [
                'Authorization' => $token->getAuthHeaderValue(),
            ],
            'query' => [
                'page' => $page,
                'per_page' => $perPage,
            ],
        ]);

        /**
         * @var array{
         *  id: int,
         *  device_name: string,
         *  name: string,
         *  start_date: int,
         *  timezone: string,
         *  elapsed_time: int,
         *  moving_time: int,
         *  distance: float,
         *  max_speed: float,
         *  average_speed: float,
         *  type: string
         * }[] $activities
         */
        $activities = $response->toArray();

        return array_map(
            static fn (array $data) => new Activity($data),
            $activities
        );
    }
}
