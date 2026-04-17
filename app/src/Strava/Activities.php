<?php

declare(strict_types=1);

namespace App\Strava;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Activities
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private ApiTokenCache $cache,
    ) {
    }

    /** @return Activity[] */
    public function fetchActivities(int $stravaUserId, int $page = 1, int $perPage = 5): array
    {
        $token = $this->cache->getToken($stravaUserId);

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

    public function dumpActivity(int $stravaUserId, int $activityId, string $destinationFolder): string
    {
        $activity = $this->fetchStreams(
            $stravaUserId,
            $activityId
        );

        $filename = sprintf('activity_stream_%d_%d.json', $stravaUserId, $activityId);
        $filePath = $destinationFolder.'/'.$filename;

        Assert::that(!file_exists($filePath), new AlreadyDownloadedException($filePath));

        $data = [
            'time' => $activity->time ? [
                'data' => $activity->time->data,
                'series_type' => $activity->time->seriesType,
                'resolution' => $activity->time->resolution,
                'original_size' => $activity->time->originalSize,
            ] : null,
            'distance' => $activity->distance ? [
                'data' => $activity->distance->data,
                'series_type' => $activity->distance->seriesType,
                'resolution' => $activity->distance->resolution,
                'original_size' => $activity->distance->originalSize,
            ] : null,
            'velocity' => $activity->velocity ? [
                'data' => $activity->velocity->data,
                'series_type' => $activity->velocity->seriesType,
                'resolution' => $activity->velocity->resolution,
                'original_size' => $activity->velocity->originalSize,
            ] : null,
            'heartrate' => $activity->heartrate ? [
                'data' => $activity->heartrate->data,
                'series_type' => $activity->heartrate->seriesType,
                'resolution' => $activity->heartrate->resolution,
                'original_size' => $activity->heartrate->originalSize,
            ] : null,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode activity stream data to JSON');
        }

        $written = file_put_contents($filePath, $json);
        if (false === $written) {
            throw new \RuntimeException(sprintf('Failed to write to file: %s', $filePath));
        }

        return $filePath;
    }

    private function fetchStreams(int $stravaUserId, int $activityId): ActivityStream
    {
        $token = $this->cache->getToken($stravaUserId);

        Assert::that(!$token->isExpired(), 'Token is expired!');

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

        return ActivityStream::fromArray($data);
    }
}
