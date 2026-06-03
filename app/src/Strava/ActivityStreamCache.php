<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Assert\Assert;
use App\Strava\API\ActivityStream;
use App\Strava\API\ActivityStreamDownloader;
use League\Flysystem\Filesystem;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class ActivityStreamCache
{
    public function __construct(
        private SerializerInterface $serializer,
        private ActivityStreamDownloader $downloader,
        private Filesystem $filesystem,
    ) {
    }

    public function get(int $stravaUserId, int $activityId): ActivityStream
    {
        $path = $this->getCachePath($stravaUserId, $activityId);

        if ($this->filesystem->has($path)) {
            $json = $this->filesystem->read($path);

            /** @var ActivityStream $object */
            $object = $this->serializer->deserialize($json, ActivityStream::class, 'json');

            return $object;
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

        $json = $this->serializer->serialize($stream, 'json');

        $this->filesystem->write($path, $json);
    }

    public function clear(int $stravaUserId, int $activityId): void
    {
        $path = $this->getCachePath($stravaUserId, $activityId);

        Assert::that($this->filesystem->has($path),
            sprintf(
                'ActivityStream "%s" does not exist. Check via ActivityStreamCache::isCached() first!',
                $path
            )
        );

        $this->filesystem->delete($path);
    }

    private function getCachePath(int $stravaUserId, int $activityId): string
    {
        return sprintf('activity_stream_%d_%d.json', $stravaUserId, $activityId);
    }
}
