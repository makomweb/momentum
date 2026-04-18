<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class ActivityStreamPersistence
{
    public function __construct(
        private Activities $activities,
        private ActivityStreamCache $cache,
        private ActivityEntityRepositoryInterface $repository,
    ) {
    }

    public function fetchAndPersist(int $stravaUserId, int $activityId): ActivityStream
    {
        if ($this->cache->isCached($stravaUserId, $activityId)) {
            $cached = $this->cache->get($stravaUserId, $activityId);
            if ($cached instanceof ActivityStream) {
                return $cached;
            }
        }

        $stream = $this->activities->fetchStreams($stravaUserId, $activityId);

        $this->cache->set($stravaUserId, $activityId, $stream);

        return $stream;
    }

    public function persistActivity(int $stravaUserId, int $activityId): void
    {
        $existing = $this->repository->findByStravaId($stravaUserId, $activityId);
        if (null !== $existing) {
            return;
        }

        $activities = $this->activities->fetchActivities($stravaUserId);
        $activity = null;

        foreach ($activities as $act) {
            if ($act->getId() === $activityId) {
                $activity = $act;
                break;
            }
        }

        if (null === $activity) {
            throw new \RuntimeException(sprintf('Activity with ID %d not found for user %d', $activityId, $stravaUserId));
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
