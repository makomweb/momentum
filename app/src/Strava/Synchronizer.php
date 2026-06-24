<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\ActivityFetcher;
use Psr\Log\LoggerInterface;

final readonly class Synchronizer
{
    public function __construct(
        private ActivityRepositoryInterface $repository,
        private ActivityFetcher $fetcher,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Sync activities for a Strava user, resuming from last activity.
     */
    public function sync(int $stravaUserId, ReportProgressInterface $progress): void
    {
        $page = 1;
        $last = $this->repository->findLastOrNull($stravaUserId);

        $progress->onStart($last);

        $after = $last
            ? $last->getStartDateUniversalTime()
            : new \DateTimeImmutable('2026-05-31 00:00:01Z');

        $this->logger->info(sprintf('Fetch all activities after %s', $after->format(\DateTimeImmutable::RFC2822)));

        do {
            $activities = $this->tryFetch($stravaUserId, $page, $after->getTimestamp());
            ++$page;

            foreach ($activities as $activity) {
                $existing = $this->repository->findOrNull($stravaUserId, $activity->getId());
                if ($existing instanceof Activity) {
                    $progress->onSkip($activity);
                    continue;
                }

                $this->repository->persist($stravaUserId, $activity);

                $progress->onSuccess($activity);
            }
        } while (!empty($activities));

        $progress->onFinish();
    }

    /**
     * Fetch activities with pagination and time filtering.
     *
     * @return Activity[]
     */
    private function tryFetch(int $stravaUserId, int $page, int $after): array
    {
        try {
            return $this->fetcher->fetch(stravaUserId: $stravaUserId, page: $page, after: $after);
        } catch (\Throwable $ex) {
            $this->logger->error(
                sprintf('Fetching page %d after %s has failed due to %s (%s)',
                    $page,
                    \DateTimeImmutable::createFromTimestamp($after)->format(\DateTimeImmutable::RFC2822),
                    $ex->getMessage(),
                    get_class($ex)
                )
            );

            return [];
        }
    }
}
