<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\ActivityStream;
use App\Strava\API\Assert;

final readonly class ActivityStreamCache
{
    public function __construct(private string $cacheDir)
    {
    }

    public function isCached(int $stravaUserId, int $activityId): bool
    {
        return is_file($this->getCacheFilePath($stravaUserId, $activityId));
    }

    public function get(int $stravaUserId, int $activityId): ActivityStream
    {
        $filePath = $this->getCacheFilePath($stravaUserId, $activityId);

        self::ensureFileExists($filePath);

        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Failed to read cache file: %s', $filePath));
        }

        /**
         * @var array<string, array{
         *     data: (int|float)[],
         *     series_type: string,
         *     resolution: string,
         *     original_size: int
         * }> $data
         */
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return ActivityStream::fromArray($data);
    }

    public function set(int $stravaUserId, int $activityId, ActivityStream $stream): void
    {
        $filePath = $this->getCacheFilePath($stravaUserId, $activityId);

        Assert::that(is_file($filePath), sprintf('ActivityStream "%s" already exists!', $filePath));

        $data = [
            'time' => $stream->time ? [
                'data' => $stream->time->data,
                'series_type' => $stream->time->seriesType,
                'resolution' => $stream->time->resolution,
                'original_size' => $stream->time->originalSize,
            ] : null,
            'distance' => $stream->distance ? [
                'data' => $stream->distance->data,
                'series_type' => $stream->distance->seriesType,
                'resolution' => $stream->distance->resolution,
                'original_size' => $stream->distance->originalSize,
            ] : null,
            'velocity' => $stream->velocity ? [
                'data' => $stream->velocity->data,
                'series_type' => $stream->velocity->seriesType,
                'resolution' => $stream->velocity->resolution,
                'original_size' => $stream->velocity->originalSize,
            ] : null,
            'heartrate' => $stream->heartrate ? [
                'data' => $stream->heartrate->data,
                'series_type' => $stream->heartrate->seriesType,
                'resolution' => $stream->heartrate->resolution,
                'original_size' => $stream->heartrate->originalSize,
            ] : null,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (false === $json) {
            throw new \RuntimeException('Failed to encode activity stream data to JSON');
        }

        $written = file_put_contents($filePath, $json);
        if (false === $written) {
            throw new \RuntimeException(sprintf('Failed to write to cache file: %s', $filePath));
        }
    }

    public function clear(int $stravaUserId, int $activityId): void
    {
        $filePath = $this->getCacheFilePath($stravaUserId, $activityId);

        self::ensureFileExists($filePath);

        if (!unlink($filePath)) {
            throw new \RuntimeException(sprintf('Failed to delete cache file: %s', $filePath));
        }
    }

    private static function ensureFileExists(string $filePath): void
    {
        Assert::that(
            is_file($filePath),
            sprintf(
                'ActivityStream "%s" does not exist. Call ActivityStreamCache::isCached() first!',
                $filePath
            )
        );
    }

    private function getCacheFilePath(int $stravaUserId, int $activityId): string
    {
        return $this->cacheDir.sprintf('/activity_stream_%d_%d.json', $stravaUserId, $activityId);
    }
}
