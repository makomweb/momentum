<?php

declare(strict_types=1);

namespace App\Strava;

use App\Entity\ActivityEntity;

interface ActivityEntityRepositoryInterface
{
    public function findByStravaId(int $stravaUserId, int $stravaActivityId): ?ActivityEntity;

    public function persist(int $stravaUserId, Activity $activity): ActivityEntity;

    /**
     * @return ActivityEntity[]
     */
    public function findByStravaUser(int $stravaUserId): array;
}
