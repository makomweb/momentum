<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\ZoneAnalysis;
use App\Momentum\ZoneTransitionTracker;
use App\Repository\ActivityRepository;
use App\Strava\ActivityData;
use App\Strava\ActivityStreamCache;
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
    ) {
    }

    #[Route('/latest', name: 'analysis', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyzeLatestActivity(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $activity = $this->activityRepository->findLastOrNull($user->getStravaId());

        if (null === $activity) {
            return $this->render('activity/analysis_empty.html.twig');
        }

        return $this->analyzeActivity($user, $activity->getId());
    }

    #[Route('/{id}', name: 'analysis_by_id', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyzeActivityById(int $id): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->analyzeActivity($user, $id);
    }

    #[Route('/{id}/zones', name: 'zones_api', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function getZonesApi(int $id): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $activity = $this->activityRepository->findOrNull($user->getStravaId(), $id);

        if (null === $activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        $ftp = $user->getFtp();
        $maxHeartRate = $user->getMaxHeartRate();

        try {
            $stream = $this->streamCache->get(
                $user->getStravaId(),
                $activity->getId()
            );
        } catch (\Exception) {
            return $this->json(['error' => 'Activity stream not available'], 404);
        }

        if (!$stream->hasData()) {
            return $this->json(['error' => 'Activity stream has no data'], 404);
        }

        // Create zone sessions from the activity stream
        $tracker = new ZoneTransitionTracker();

        $startDate = $activity->getStartDateLocalTime();

        foreach ($stream->yield($startDate, 50) as $point) {
            $velocity = $point['velocity'];
            $heartRate = $point['heart_rate'];

            if (null === $velocity || $velocity->metersPerSecond < 0.1) {
                continue;
            }

            // Use smoothed velocity from ActivityStream
            $smoothedVelocity = $point['velocity_avg'] ?? $velocity;

            if ($smoothedVelocity->metersPerSecond < 0.1) {
                continue;
            }

            // Convert to pace and reject unreasonably slow paces (> 60 min/km)
            $pace = $smoothedVelocity->asPace();
            if ($pace->value > 60.0) {
                continue;
            }

            $tracker->processPoint(
                timestamp: $point['timestamp'],
                pace: $pace,
                ftp: $ftp,
                heartRate: $heartRate?->value,
                maxHeartRate: $maxHeartRate,
            );
        }

        // Get end time from the activity's moving time
        $movingTimeInterval = $activity->getMovingTime();
        $endTime = $startDate->add($movingTimeInterval);

        $tracker->finalize($endTime);

        $paceZoneSessions = $tracker->getPaceZoneSessions();
        $hrZoneSessions = $tracker->getHeartRateZoneSessions();

        $analysis = new ZoneAnalysis(
            paceZoneSessions: $paceZoneSessions,
            heartRateZoneSessions: $hrZoneSessions,
            totalActivitySeconds: (int) $movingTimeInterval->format('%s'),
        );

        $paceStats = $analysis->getPaceZoneStatistics();
        $hrStats = $analysis->getHeartRateZoneStatistics();
        $metrics = $analysis->getTrainingMetrics();

        return $this->json([
            'activity' => [
                'id' => $activity->getId(),
                'name' => $activity->getName(),
                'start_date' => $startDate->format('c'),
            ],
            'pace_zones' => array_map(
                fn (array $zone) => [
                    'zone' => $zone['zone']->value,
                    'duration_seconds' => $zone['duration_seconds'],
                    'duration_percentage' => round($zone['duration_percentage'], 2),
                ],
                $paceStats->all()
            ),
            'heart_rate_zones' => array_map(
                fn (array $zone) => [
                    'zone' => $zone['zone']->value,
                    'duration_seconds' => $zone['duration_seconds'],
                    'duration_percentage' => round($zone['duration_percentage'], 2),
                ],
                $hrStats->all()
            ),
            'polarized_training' => [
                'easy_seconds' => $metrics->easySeconds,
                'easy_percentage' => round($metrics->getEasyPercentage(), 2),
                'tempo_seconds' => $metrics->getTempoSeconds(),
                'tempo_percentage' => round($metrics->getTempoPercentage(), 2),
                'hard_seconds' => $metrics->hardSeconds,
                'hard_percentage' => round($metrics->getHardPercentage(), 2),
            ],
        ]);
    }

    private function analyzeActivity(User $user, int $stravaActivityId): Response
    {
        $activity = $this->activityRepository->findOrNull($user->getStravaId(), $stravaActivityId);

        if (null === $activity) {
            return $this->render('activity/analysis_empty.html.twig');
        }

        try {
            $stream = $this->streamCache->get(
                $user->getStravaId(),
                $activity->getId()
            );
        } catch (\Exception) {
            return $this->render('activity/analysis_no_stream.html.twig', [
                'activity_name' => $activity->getName(),
            ]);
        }

        if (!$stream->hasData()) {
            return $this->render('activity/analysis_no_stream.html.twig', [
                'activity_name' => $activity->getName(),
            ]);
        }

        $activityData = new ActivityData($activity, $stream);
        $metaData = $activityData->getMetaData();

        $analysis = null;
        $ftp = $user->getFtp();
        $maxHeartRate = $user->getMaxHeartRate();

        if (!$ftp->isZero()) {
            $tracker = new ZoneTransitionTracker();
            $startDate = $activity->getStartDateLocalTime();

            foreach ($stream->yield($startDate, 50) as $point) {
                $velocity = $point['velocity'];
                $heartRate = $point['heart_rate'];

                if (null === $velocity || $velocity->metersPerSecond < 0.1) {
                    continue;
                }

                $smoothedVelocity = $point['velocity_avg'] ?? $velocity;

                if ($smoothedVelocity->metersPerSecond < 0.1) {
                    continue;
                }

                // Convert to pace and reject unreasonably slow paces (> 60 min/km)
                $pace = $smoothedVelocity->asPace();
                if ($pace->value > 60.0) {
                    continue;
                }

                $tracker->processPoint(
                    timestamp: $point['timestamp'],
                    pace: $pace,
                    ftp: $ftp,
                    heartRate: $heartRate?->value,
                    maxHeartRate: $maxHeartRate,
                );
            }

            $movingTimeInterval = $activity->getMovingTime();
            $endTime = $startDate->add($movingTimeInterval);
            $tracker->finalize($endTime);

            $paceZoneSessions = $tracker->getPaceZoneSessions();
            $hrZoneSessions = $tracker->getHeartRateZoneSessions();

            $analysis = new ZoneAnalysis(
                paceZoneSessions: $paceZoneSessions,
                heartRateZoneSessions: $hrZoneSessions,
                totalActivitySeconds: (int) $movingTimeInterval->format('%s'),
            );
        }

        return $this->render('activity/analysis.html.twig', [
            'activityData' => $activityData,
            'metaData' => $metaData,
            'zoneAnalysis' => $analysis,
        ]);
    }
}
