<?php

declare(strict_types=1);

namespace App\Strava;

interface ActivityEntityRepositoryInterface
{
    public function findByStravaId(int $stravaUserId, int $stravaActivityId): ?Activity;

    public function persist(int $stravaUserId, Activity $activity): void;

    /**
     * @return Activity[]
     */
    public function findByStravaUser(int $stravaUserId): array;
}
