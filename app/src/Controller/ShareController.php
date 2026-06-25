<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\ActivityBragMessage;
use App\Repository\ActivityRepository;
use App\Strava\ActivityShareTokenGenerator;
use App\Strava\ActivityStreamCache;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route('', name: 'share_')]
final class ShareController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityStreamCache $streamCache,
        private ActivityShareTokenGenerator $tokenGenerator,
        private ActivityBragMessage $bragMessage,
    ) {
    }

    #[Route('/share/{token}', name: 'activity', methods: ['GET'], requirements: ['token' => '[a-z0-9:.]+'])]
    public function view_shared_activity(string $token): Response
    {
        try {
            $result = $this->tokenGenerator->validate($token);
        } catch (AccessDeniedHttpException $e) {
            return $this->render('share/expired.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(status: Response::HTTP_GONE));
        }

        $activity = $this->activityRepository->findByStravaActivityId($result['strava_activity_id']);

        if (null === $activity) {
            return $this->render('share/not_found.html.twig', [], new Response(status: Response::HTTP_NOT_FOUND));
        }

        $activityData = $activity->getData();
        $activityName = $activityData['name'];

        try {
            $stream = $this->streamCache->get(
                $activity->getStravaUserId(),
                $activity->getId()
            );
        } catch (\Exception) {
            return $this->render('share/no_stream.html.twig', [
                'activity_name' => $activityName,
            ]);
        }

        if (!$stream->hasData()) {
            return $this->render('share/no_stream.html.twig', [
                'activity_name' => $activityName,
            ]);
        }

        $message = $this->bragMessage->generate($activityData);

        return $this->render('share/activity.html.twig', [
            'activity_name' => $activityData['name'],
            'message' => $message,
            'distance' => $activityData['distance'],
            'moving_time' => $activity->getMovingTime(),
            'average_velocity' => $activity->getAverageVelocityAsStringable(),
            'start_date' => $activity->getStartDateFormatted(),
            'type' => $activityData['type'],
        ]);
    }
}
