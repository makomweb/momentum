<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\ActivityShareToken;
use App\Momentum\AthleteInterface;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// TODO: Consolidate this controller with ShareController + BragMessage!
#[Route('/activity', name: 'activity_')]
final class ActivityShareController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityShareToken $shareToken,
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
        $expiryDays = (int) $data->get('expires_in', 30);

        // Validate expiry days (between 1 and 365)
        if ($expiryDays < 1 || $expiryDays > 365) {
            return $this->json(['error' => 'expires_in must be between 1 and 365 days'], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->shareToken->generate($stravaUserId, $activity->getId(), $expiryDays);

        return $this->json([
            ...$data,
            'share_url' => $this->generateUrl('share_activity', ['token' => $data['token']], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }
}
