<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\ActivityFetcher;

final readonly class Synchronizer
{
    public function __construct(
        private ActivityRepositoryInterface $repository,
        private ActivityFetcher $fetcher,
    ) {
    }

    /**
     * Sync activities for a Strava user, resuming from last activity.
     */
    public function sync(int $stravaUserId, ReportProgressInterface $progress): void
    {
        $page = 0;
        $pageSize = 200;
        $last = $this->repository->findLastOrNull($stravaUserId);

        if ($last) {
            $progress->onStart($last);
        }

        do {
            $activities = $this->fetcher->fetch(
                stravaUserId: $stravaUserId,
                page: ++$page,
                perPage: $pageSize,
                after: $last ? $last->getStartDateUniversalTime()->getTimestamp() : null
                // after: new \DateTimeImmutable('2026-01-01 00:00:01Z')->getTimestamp()
            );

            foreach ($activities as $activity) {
                $existing = $this->repository->findOrNull($stravaUserId, $activity->getId());
                if ($existing instanceof Activity) {
                    continue;
                }

                $this->repository->persist($stravaUserId, $activity);

                $progress->onActivity($activity);
            }
        } while (!empty($activities));

        $progress->onFinish();
    }
}
