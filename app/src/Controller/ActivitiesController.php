<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\AthleteInterface;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ActivitiesController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private AthleteInterface $athlete,
    ) {
    }

    #[Route('/activities', name: 'activities_index', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $activities = $this->activityRepository->findRecentByStravaUser($this->athlete->getId(), 20);

        return $this->render('activities/index.html.twig', [
            ...$this->athlete->toArray(),
            'activities' => $activities,
        ]);
    }

    #[Route('/activities', name: 'activities_clear', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function clear(): JsonResponse
    {
        $this->activityRepository->wipe($this->athlete->getId());

        return $this->json(['message' => 'cleared all activites', 'strava_user_id' => $this->athlete->getId()]);
    }
}
