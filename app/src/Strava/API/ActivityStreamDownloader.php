<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Assert\Assert;
use App\Strava\ActivityStreamFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ActivityStreamDownloader
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private TokenCacheInterface $tokenCache,
        private ActivityStreamFactory $factory,
    ) {
    }

    public function download(int $stravaUserId, int $activityId): ActivityStream
    {
        $token = $this->tokenCache->getToken($stravaUserId);

        Assert::that(!$token->isExpired(), new TokenExpiredException($stravaUserId));

        $response = $this->httpClient->request('GET', sprintf(
            'https://www.strava.com/api/v3/activities/%d/streams',
            $activityId
        ), [
            'headers' => [
                'Authorization' => $token->getAuthHeaderValue(),
            ],
            'query' => [
                'keys' => 'time,distance,heartrate,velocity_smooth',
                'key_by_type' => 'true',
            ],
        ]);

        /**
         * @var array<string, array{
         *   data: (int|float)[],
         *   series_type: string,
         *   resolution: string,
         *   original_size: int
         * }> $data
         */
        $data = $response->toArray();

        return $this->factory->fromArray($data);
    }
}
