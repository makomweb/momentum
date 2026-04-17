<?php

declare(strict_types=1);

namespace App\Strava;

use App\Repository\ActivityEntityRepository;

final readonly class ActivityStreamPersistence
{
    public function __construct(
        private Activities $activities,
        private ActivityStreamCache $cache,
        private ActivityEntityRepository $repository,
    ) {
    }

    public function fetchAndPersist(int $stravaUserId, int $activityId): ActivityStream
    {
        // Try cache first
        if ($this->cache->isCached($stravaUserId, $activityId)) {
            $cached = $this->cache->get($stravaUserId, $activityId);
            if ($cached instanceof ActivityStream) {
                return $cached;
            }
        }

        // Fetch from API
        $stream = $this->activities->fetchStreams($stravaUserId, $activityId);

        // Persist to cache
        $this->cache->set($stravaUserId, $activityId, $stream);

        return $stream;
    }

    public function persistActivity(int $stravaUserId, int $activityId): void
    {
        // Check if already persisted
        $existing = $this->repository->findByStravaId($stravaUserId, $activityId);
        if ($existing !== null) {
            return;
        }

        // Fetch activity details (from Activities API, not streams)
        $activities = $this->activities->fetchActivities($stravaUserId);
        $activity = null;

        foreach ($activities as $act) {
            if ($act->getId() === $activityId) {
                $activity = $act;
                break;
            }
        }

        if ($activity === null) {
            throw new \RuntimeException(sprintf(
                'Activity with ID %d not found for user %d',
                $activityId,
                $stravaUserId
            ));
        }

        $this->repository->persist($stravaUserId, $activity);
    }

    public function fetchAndCacheStreams(int $stravaUserId, int $activityId): ActivityStream
    {
        $stream = $this->fetchAndPersist($stravaUserId, $activityId);
        $this->persistActivity($stravaUserId, $activityId);

        return $stream;
    }
}
