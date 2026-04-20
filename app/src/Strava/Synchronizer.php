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
        $pageSize = 20; //200;
        $last = $this->repository->findLastOrNull($stravaUserId);

        //do {
            $activities = $this->fetcher->fetch(
                stravaUserId: $stravaUserId,
                page: ++$page,
                perPage: $pageSize,
                after: $last ? $last->getStartDateUniversalTime()->getTimestamp() : null
            );

            foreach ($activities as $activity) {
                $existing = $this->repository->findOrNull($stravaUserId, $activity->getId());
                if ($existing instanceof Activity) {
                    continue;
                }

                $this->repository->persist($stravaUserId, $activity);

                $progress->onActivity($activity);
            }
        //} while (!empty($activities));

        $progress->onFinish();
    }
}
