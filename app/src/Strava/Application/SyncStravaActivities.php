<?php

declare(strict_types=1);

namespace App\Strava\Application;

use App\Entity\ActivitySyncState;
use App\Repository\ActivitySyncStateRepository;
use App\Strava\ActivityRepositoryInterface;
use App\Strava\API\ActivityFetcher;

final readonly class SyncStravaActivities
{
    private const BATCH_SIZE = 20; //200;

    public function __construct(
        private ActivityFetcher $fetcher,
        private ActivityRepositoryInterface $repository,
        private ActivitySyncStateRepository $syncStateRepository,
    ) {
    }

    /**
     * Sync activities for a Strava user, resuming from last sync.
     *
     * @return array{
     *   synced_count: int,
     *   total_pages: int,
     *   last_activity_timestamp: int|null
     * }
     */
    public function sync(int $stravaUserId, ReportProgressInterface $progress): array
    {
        $syncState = $this->syncStateRepository->findOrCreateForUser($stravaUserId);

        $syncedCount = 0;
        $totalPages = 0;
        $mostRecentTimestamp = $syncState->getLastActivityTimestamp();

        $page = 1;
        $shouldContinue = true;

        // while ($shouldContinue) {
        $activities = $this->fetcher->fetch(
            $stravaUserId,
            $page,
            self::BATCH_SIZE,
            $mostRecentTimestamp
        );

        if (empty($activities)) {
            $shouldContinue = false;
            // break;
        }

        ++$totalPages;

        foreach ($activities as $activity) {
            // Skip if already synced
            $existing = $this->repository->findByStravaId($stravaUserId, $activity->getId());
            if (null !== $existing) {
                continue;
            }

            $progress->report($activity);

            try {
                $this->repository->persist($stravaUserId, $activity);
                ++$syncedCount;

                // Track the most recent (first) activity timestamp for resume
                if (null === $mostRecentTimestamp || $activity->getStartTime()->getTimestamp() > $mostRecentTimestamp) {
                    $mostRecentTimestamp = $activity->getStartTime()->getTimestamp();
                }
            } catch (\Throwable $ex) {
                $progress->reportException($activity, $ex);
                continue;
            }
        }

        // Stop if we got fewer results than batch size (last page)
        if (\count($activities) < self::BATCH_SIZE) {
            $shouldContinue = false;
        }

        ++$page;
        // }

        // Mark sync as completed
        if (null !== $mostRecentTimestamp) {
            $syncState->markSynced($mostRecentTimestamp);
            $this->syncStateRepository->save($syncState);
        }

        return [
            'synced_count' => $syncedCount,
            'total_pages' => $totalPages,
            'last_activity_timestamp' => $mostRecentTimestamp,
        ];
    }

    /**
     * Force a full re-sync by clearing sync state.
     */
    public function resetSync(int $stravaUserId): void
    {
        $syncState = $this->syncStateRepository->findOrCreateForUser($stravaUserId);

        // Create new sync state without modifying DB
        $newState = ActivitySyncState::create($stravaUserId);
        $this->syncStateRepository->save($newState);
    }
}
