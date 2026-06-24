<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Momentum\AthleteInterface;
use App\Repository\ActivityRepository;
use App\Strava\Service\ActivityShareTokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/activities', name: 'api_activities_')]
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
    public function generate_share(int $id, Request $request): JsonResponse
    {
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

        $result = $this->tokenGenerator->generate($activity->getId(), $expiryDays);

        return $this->json([
            'token' => $result['token'],
            'expiresAt' => $result['expiresAt']->format(\DateTimeInterface::ATOM),
            'shareUrl' => $this->generateUrl('share_activity', ['token' => $result['token']], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
