<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Assert\Assert;
use App\Strava\API\ActivityStream;
use App\Strava\API\ActivityStreamDownloader;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;

final readonly class ActivityStreamCache
{
    public function __construct(
        private ActivityStreamDownloader $downloader,
        private Filesystem $filesystem,
    ) {
    }

    public function get(int $stravaUserId, int $activityId): ActivityStream
    {
        $path = $this->getCachePath($stravaUserId, $activityId);

        try {
            if ($this->filesystem->has($path)) {
                $contents = $this->filesystem->read($path);

                return ActivityStream::fromJsonString($contents);
            }
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to read activity stream from cache: %s', $e->getMessage()), 0, $e);
        }

        $stream = $this->downloader->download($stravaUserId, $activityId);
        $this->set($stravaUserId, $activityId, $stream);

        return $stream;
    }

    private function set(int $stravaUserId, int $activityId, ActivityStream $stream): void
    {
        $path = $this->getCachePath($stravaUserId, $activityId);

        Assert::that(
            !$this->filesystem->has($path),
            sprintf('ActivityStream "%s" already exists!', $path)
        );

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

        try {
            $this->filesystem->write($path, $json);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to write activity stream to cache: %s', $e->getMessage()), 0, $e);
        }
    }

    public function clear(int $stravaUserId, int $activityId): void
    {
        $path = $this->getCachePath($stravaUserId, $activityId);

        Assert::that(
            $this->filesystem->has($path),
            sprintf(
                'ActivityStream "%s" does not exist. Call ActivityStreamCache::isCached() first!',
                $path
            )
        );

        try {
            $this->filesystem->delete($path);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to delete activity stream cache: %s', $e->getMessage()), 0, $e);
        }
    }

    private function getCachePath(int $stravaUserId, int $activityId): string
    {
        return sprintf('activity_stream_%d_%d.json', $stravaUserId, $activityId);
    }
}
