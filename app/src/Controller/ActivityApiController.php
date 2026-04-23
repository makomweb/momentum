<?php

declare(strict_types=1);

namespace App\Controller;

use App\Async\Message\SyncActivitiesMessage;
use App\Entity\User;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/activities', name: 'api_activities_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ActivityApiController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private MessageBusInterface $bus,
    ) {
    }

    #[Route('/recent', name: 'recent', methods: ['GET'])]
    public function getRecent(): JsonResponse
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $activities = $this->activityRepository->findRecentByStravaUser(
            $user->getStravaId(),
            limit: 20
        );

        $data = array_map(function ($activity) {
            $activityData = $activity->getData();

            return [
                'id' => $activity->getStravaActivityId(),
                'name' => $activityData['name'],
                'type' => $activityData['type'],
                'startDate' => $activity->getStartDate()->format('c'),
                'distance' => $activityData['distance'],
                'movingTime' => $activityData['moving_time'],
                'elapsedTime' => $activityData['elapsed_time'],
            ];
        }, $activities);

        return $this->json($data);
    }

    #[Route('/sync', name: 'sync', methods: ['POST'])]
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
