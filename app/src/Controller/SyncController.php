<?php

declare(strict_types=1);

namespace App\Controller;

use App\Async\Message\SyncActivitiesMessage;
use App\Momentum\AthleteInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class SyncController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
        private AthleteInterface $athlete,
        private CacheInterface $cache,
    ) {
    }

    #[Route('/sync', name: 'sync', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function sync(): JsonResponse
    {
        $message = SyncActivitiesMessage::for($this->athlete);

        $this->cache->get($message->getStatusCacheKey(), function (ItemInterface $item): string {
            $item->expiresAfter(300);

            return 'pending';
        });

        $this->bus->dispatch($message);

        return $this->json([
            'status' => 'syncing',
            'message' => 'Sync started in background',
            'jobId' => $message->jobId,
        ], Response::HTTP_ACCEPTED);
    }
}
