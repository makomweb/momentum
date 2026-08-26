<?php

declare(strict_types=1);

namespace App\Async\Message;

use App\Momentum\AthleteInterface;

final readonly class SyncActivitiesMessage
{
    public function __construct(
        public int $stravaUserId,
        public string $jobId,
    ) {
    }

    public static function for(AthleteInterface $athlete): self
    {
        return new self($athlete->getId(), uniqid('sync_activities_', true));
    }

    public static function createStatusCacheKey(string $jobId): string
    {
        return sprintf('sync_activities_status_%s', $jobId);
    }

    public function getStatusCacheKey(): string
    {
        return self::createStatusCacheKey($this->jobId);
    }
}
