<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\ActivityStream;
use App\Momentum\Assert\Assert;
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

    public function get(int $stravaUserId, int $stravaActivityId): ActivityStream
    {
        $fileName = self::getFileName($stravaUserId, $stravaActivityId);

        if ($this->filesystem->has($fileName)) {
            $json = $this->filesystem->read($fileName);

            return $this->serializer->deserialize($json, ActivityStream::class, 'json');
        }

        $stream = $this->downloader->download($stravaUserId, $stravaActivityId);
        $this->set($stravaUserId, $stravaActivityId, $stream);

        return $stream;
    }

    private function set(int $stravaUserId, int $stravaActivityId, ActivityStream $stream): void
    {
        $fileName = self::getFileName($stravaUserId, $stravaActivityId);

        Assert::that(
            !$this->filesystem->has($fileName),
            sprintf('ActivityStream "%s" already exists!', $fileName)
        );

        $json = $this->serializer->serialize($stream, 'json');

        $this->filesystem->write($fileName, $json);
    }

    public function clear(int $stravaUserId, int $stravaActivityId): void
    {
        $fileName = self::getFileName($stravaUserId, $stravaActivityId);

        Assert::that($this->filesystem->has($fileName),
            sprintf(
                'ActivityStream "%s" does not exist. Check via ActivityStreamCache::isCached() first!',
                $fileName
            )
        );

        $this->filesystem->delete($fileName);
    }

    private static function getFileName(int $stravaUserId, int $stravaActivityId): string
    {
        return sprintf('activity_stream_%d_%d.json', $stravaUserId, $stravaActivityId);
    }
}
