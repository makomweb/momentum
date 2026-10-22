<?php

namespace App\Async\Handler;

use App\Strava\API\Activity;
use App\Strava\DownloadProgressInterface;
use Psr\Log\LoggerInterface;

final class DownloadProgressAdapter implements DownloadProgressInterface
{
    public function __construct(
        private LoggerInterface $logger,
        public int $count = 0,
    ) {
    }

    public function onStart(?Activity $resumeAfter): void
    {
        if ($resumeAfter) {
            $this->logger->info(sprintf('🚀 Resume after "%s"', $resumeAfter));
        } else {
            $this->logger->info('🚀 Start synchronizing');
        }
    }

    public function onSkip(Activity $activity): void
    {
        $this->logger->info(sprintf('Activity %s already in database present', $activity));
    }

    public function onSuccess(Activity $activity): void
    {
        ++$this->count;
    }

    public function onFinish(): void
    {
        $this->logger->info(sprintf('✅ Synchronized %d activitie(s)', $this->count));
    }
}
