<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ActivitiesController extends AbstractController
{
    public function __construct(private ActivityRepository $activityRepository)
    {
    }

    #[Route('/activities', name: 'activities', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $activities = $this->activityRepository->findRecentByStravaUser($user->getStravaId(), 20);

        $isConfigured = !$user->getTreshold()->isZero() && $user->getMaxHeartRate()->isValid();

        return $this->render('activities/index.html.twig', [
            'name' => $user->getName(),
            'strava_user_id' => $user->getStravaId(),
            'profile_picture' => $user->getProfilePictureMedium(),
            'activities' => $activities,
            'isConfigured' => $isConfigured,
        ]);
    }
}
