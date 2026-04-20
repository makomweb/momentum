<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\ActivityStream;
use App\Strava\API\ActivityStreamDownloader;

final readonly class ActivityStreamPersistence
{
    public function __construct(
        private ActivityStreamCache $cache,
        private ActivityStreamDownloader $downloader,
    ) {
    }

    public function get(int $stravaUserId, int $activityId): ActivityStream
    {
        if ($this->cache->isCached($stravaUserId, $activityId)) {
            $cached = $this->cache->get($stravaUserId, $activityId);
            if ($cached instanceof ActivityStream) {
                return $cached;
            }
        }

        $stream = $this->downloader->download($stravaUserId, $activityId);

        $this->cache->set($stravaUserId, $activityId, $stream);

        return $stream;
    }
}
