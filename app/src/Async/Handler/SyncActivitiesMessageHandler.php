<?php
declare(strict_types=1);

namespace App\Async\Handler;

use App\Async\Message\SyncActivitiesMessage;
use App\Command\ReportProgressAdapter;
use App\Strava\Synchronizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncActivitiesMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private Synchronizer $synchronizer,
    ) {
    }

    public function __invoke(SyncActivitiesMessage $message): void
    {
        $stravaUserId = $message->stravaUserId;
        $this->logger->info(sprintf('Syncing activities for user "%d"', $stravaUserId));
        $this->synchronizer->sync($stravaUserId, new ReportProgressAdapter($this->logger));
    }
}
