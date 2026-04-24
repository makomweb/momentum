<?php

declare(strict_types=1);

namespace App\Controller;

use App\Async\Message\SyncActivitiesMessage;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SyncController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/sync', name: 'sync', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function sync(): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $this->bus->dispatch(new SyncActivitiesMessage($user->getStravaId()));

        return $this->json([
            'status' => 'syncing',
            'message' => 'Sync started in background',
        ], Response::HTTP_ACCEPTED);
    }
}
