<?php

namespace App\Async\Handler;

use App\Strava\API\Activity;
use App\Strava\ReportProgressInterface;
use Psr\Log\LoggerInterface;

final class ReportProgressAdapter implements ReportProgressInterface
{
    public function __construct(
        private LoggerInterface $logger,
        public int $count = 0,
    ) {
    }

    public function reportStart(?Activity $resumeAfter): void
    {
        if ($resumeAfter) {
            $this->logger->info(sprintf('🚀 Resume after "%s"', $resumeAfter));
        } else {
            $this->logger->info('🚀 Start synchronizing');
        }
    }

    public function skip(Activity $activity): void
    {
    }

    public function report(Activity $activity): void
    {
        ++$this->count;
    }

    public function reportFinish(): void
    {
        $this->logger->info(sprintf('Synchronized %d activitie(s) ✅', $this->count));
    }
}
