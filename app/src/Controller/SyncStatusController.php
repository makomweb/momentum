<?php

declare(strict_types=1);

namespace App\Controller;

use App\Async\Message\SyncActivitiesMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SyncStatusController extends AbstractController
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    #[Route('/sync/status/{jobId}', name: 'sync_status_stream', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function streamStatus(string $jobId): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($jobId): void {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no');

            // Flush initial output
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
            flush();

            $startTime = time();
            $timeout = 300; // 5 minutes timeout

            $cacheKey = SyncActivitiesMessage::createStatusCacheKey($jobId);

            while ((time() - $startTime) < $timeout) {
                // Check if sync is complete by trying to get the item
                $status = $this->cache->get($cacheKey, function (ItemInterface $item): string {
                    $item->expiresAfter(0);

                    return 'pending';
                });

                // @phpstan-ignore-next-line -- Cache is set to 'complete' by async handler
                if ('complete' === $status) {
                    echo "event: sync-complete\n";
                    echo "data: {}\n\n";
                    flush();
                    break;
                }

                // Send heartbeat every second to keep connection alive
                echo ": heartbeat\n\n";
                flush();

                sleep(1);
            }

            // Cleanup
            $this->cache->delete($cacheKey);
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
