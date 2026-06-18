<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Assert\Assert;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ActivityFetcher
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private TokenCacheInterface $tokenCache,
    ) {
    }

    /**
     * Fetch activities with pagination and optional time filtering.
     *
     * @return Activity[]
     */
    public function fetch(
        int $stravaUserId,
        int $page = 1,
        int $perPage = 30,
        ?int $after = null,
        ?int $before = null,
    ): array {
        $token = $this->tokenCache->getToken($stravaUserId);

        Assert::that(!$token->isExpired(), new TokenExpiredException($stravaUserId));

        $query = [
            'page' => $page,
            'per_page' => $perPage,
        ];

        if ($after) {
            $query['after'] = $after;
        }

        if ($before) {
            $query['before'] = $before;
        }

        $response = $this->httpClient->request('GET', 'https://www.strava.com/api/v3/athlete/activities', [
            'headers' => [
                'Authorization' => $token->getAuthHeaderValue(),
            ],
            'query' => $query,
        ]);

        /**
         * @var array{
         *  id: int,
         *  device_name: string,
         *  name: string,
         *  start_date: string,
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
            static fn (array $data) => Activity::fromRawData($data),
            $activities
        );
    }
}
