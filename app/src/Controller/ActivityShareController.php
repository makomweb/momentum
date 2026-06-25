<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\AthleteInterface;
use App\Repository\ActivityRepository;
use App\Strava\ActivityShareTokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity', name: 'activity_')]
final class ActivityShareController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityShareTokenGenerator $tokenGenerator,
        private AthleteInterface $athlete,
    ) {
    }

    #[Route('/{id}/share', name: 'share', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function generate(int $id, Request $request): JsonResponse
    {
        $stravaUserId = $this->athlete->getStravaId();

        $activity = $this->activityRepository->findOrNull($this->athlete->getStravaId(), $id);

        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getPayload();
        $expiryDays = (int) $data->get('expiresIn', 30);

        // Validate expiry days (between 1 and 365)
        if ($expiryDays < 1 || $expiryDays > 365) {
            return $this->json(['error' => 'expiresIn must be between 1 and 365 days'], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->tokenGenerator->generate($stravaUserId, $activity->getId(), $expiryDays);

        return $this->json([
            ...$token,
            'share_url' => $this->generateUrl('share_activity', $token, UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
