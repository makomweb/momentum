<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\AthleteInterface;
use App\Momentum\TrainingMetrics;
use App\Momentum\ZoneSession;
use App\Repository\ActivityRepository;
use App\Strava\ActivityData;
use App\Strava\ActivityMetaData;
use App\Strava\ActivityStreamCache;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity', name: 'activity_')]
final class ActivityController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityStreamCache $streamCache,
        private AthleteInterface $athlete,
    ) {
    }

    #[Route('/{id}', name: 'analysis_by_id', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyzeActivityById(int $id): Response
    {
        $activity = $this->activityRepository->findOrNull($this->athlete->getStravaId(), $id);

        if (null === $activity) {
            return $this->render('activity/analysis_empty.html.twig', [
                'profile_picture' => $this->athlete->getProfilePicture(),
            ]);
        }

        try {
            $stream = $this->streamCache->get(
                $this->athlete->getStravaId(),
                $activity->getId()
            );
        } catch (\Exception) {
            return $this->render('activity/analysis_no_stream.html.twig', [
                'activity_name' => $activity->getName(),
                'profile_picture' => $this->athlete->getProfilePicture(),
            ]);
        }

        if (!$stream->hasData()) {
            return $this->render('activity/analysis_no_stream.html.twig', [
                'activity_name' => $activity->getName(),
                'profile_picture' => $this->athlete->getProfilePicture(),
            ]);
        }

        $data = $this->createData($activity, $stream);

        return $this->render('activity/analysis.html.twig', $data);
    }

    /**
     * @return array{
     *     meta_data: ActivityMetaData,
     *     metrics: TrainingMetrics,
     *     pace_zone_sessions: array<ZoneSession>,
     *     heart_rate_zone_sessions: array<ZoneSession>,
     *     pace_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     *     hr_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     * }
     */
    private function createData(Activity $activity, ActivityStream $stream): array
    {
        $activityData = new ActivityData($this->athlete, $activity, $stream);

        return [
            'profile_picture' => $this->athlete->getProfilePicture(),
            ...$activityData->toArray(),
        ];
    }
}
