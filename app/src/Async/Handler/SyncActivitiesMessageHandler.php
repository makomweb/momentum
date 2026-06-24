<?php

declare(strict_types=1);

namespace App\Async\Handler;

use App\Async\Message\SyncActivitiesMessage;
use App\Strava\API\Client;
use App\Strava\API\TokenExpiredException;
use App\Strava\Synchronizer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[AsMessageHandler]
final readonly class SyncActivitiesMessageHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private Synchronizer $synchronizer,
        private Client $stravaClient,
        private CacheInterface $cache,
    ) {
    }

    public function __invoke(SyncActivitiesMessage $message): void
    {
        $stravaUserId = $message->stravaUserId;

        $this->logger->info(sprintf('Syncing activities for user "%d"', $stravaUserId));

        $adapter = new ReportProgressAdapter($this->logger);

        try {
            $this->synchronizer->sync($stravaUserId, $adapter);
        } catch (TokenExpiredException $ex) {
            $this->tryRefreshTokenFor($stravaUserId);
            throw $ex;
        } finally {
            $this->cache->delete($message->getStatusCacheKey());
            $this->cache->get($message->getStatusCacheKey(), function (ItemInterface $item): string {
                $item->expiresAfter(10);

                return 'complete';
            });
        }
    }

    private function tryRefreshTokenFor(int $stravaUserId): void
    {
        $this->logger->notice(sprintf('Going to refresh token for user "%d"', $stravaUserId));

        try {
            $this->stravaClient->refreshTokenFor($stravaUserId);
        } catch (\Throwable $ex) {
            $this->logger->error(
                sprintf(
                    'Refreshing token for user "%d" has failed due to %s',
                    $stravaUserId,
                    $ex->getMessage()
                )
            );
        }
    }
}
