<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use App\Momentum\TrainingMetrics;
use App\Momentum\ZoneSessions;
use App\Momentum\ZoneStatistics;
use App\Repository\ActivityRepository;
use App\Strava\ActivityData;
use App\Strava\ActivityStreamCache;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
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
        // $tracker = ZoneTransitionTracker::for($user);

        // $startDate = $activity->getStartDateLocalTime();

        // foreach ($stream->yield($startDate, 50) as $point) {
        //     if ($point->hasVelocity() && !$point->getVelocityAverage()->tooSlow()) {
        //         $tracker->processPoint($point);
        //     }
        // }

        // // Get end time from the activity's moving time
        // $movingTimeInterval = $activity->getMovingTime();
        // $endTime = $startDate->add($movingTimeInterval);

        // $tracker->finalize($endTime);

        // $paceZoneSessions = $tracker->getPaceZoneSessions();
        // $hrZoneSessions = $tracker->getHeartRateZoneSessions();

        $sessions = self::createZoneSessions($user, $activity, $stream);

        $heartRateZoneSessions = $sessions->getHeartRateSessions();
        // $confirmed = ConfirmedPaceZoneSessions::from($sessions);
        // $paceZoneSessions = iterator_to_array($confirmed->yieldSessions());
        $paceZoneSessions = $sessions->getPaceZoneSessions();

        $metrics = TrainingMetrics::fromSessions($paceZoneSessions);
        $paceStats = ZoneStatistics::fromSessions($paceZoneSessions, PaceZone::cases());
        $hrStats = ZoneStatistics::fromSessions($heartRateZoneSessions, HeartRateZone::cases());

        return $this->json([
            'activity' => [
                'id' => $activity->getId(),
                'name' => $activity->getName(),
                'start_date' => $activity->getStartDateLocalTime()->format('c'),
            ],
            'pace_zones' => $paceStats->toArray($activity->getMovingTimeSeconds()),
            'heart_rate_zones' => $hrStats->toArray($activity->getMovingTimeSeconds()),
            'polarized_training' => [
                'easy_seconds' => $metrics->easySeconds,
                'easy_percentage' => round($metrics->getEasyPercentage(), 2),
                'medium_seconds' => $metrics->mediumSeconds,
                'medium_percentage' => round($metrics->getMediumPercentage(), 2),
                'hard_seconds' => $metrics->hardSeconds,
                'hard_percentage' => round($metrics->getHardPercentage(), 2),
            ],
        ]);
    }

    private static function createZoneSessions(AthleteInterface $athlete, Activity $activity, ActivityStream $stream): ZoneSessions
    {
        if ($athlete->getTreshold()->isZero()) {
            throw new HttpException(400, 'Please configure a treshold!');
        }
        if (!$athlete->getMaxHeartRate()->isValid()) {
            throw new HttpException(400, 'Please configure a max. heart rate!');
        }

        $startDate = $activity->getStartDateLocalTime();
        $dataPoints = iterator_to_array($stream->yield($startDate, 50));

        return ZoneSessions::create($athlete, $activity->getEndDateLocalTime(), $dataPoints);
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

        $sessions = self::createZoneSessions($user, $activity, $stream);

        $heartRateZoneSessions = $sessions->getHeartRateSessions();
        // $confirmed = ConfirmedPaceZoneSessions::from($sessions);
        // $paceZoneSessions = iterator_to_array($confirmed->yieldSessions());
        $paceZoneSessions = $sessions->getPaceZoneSessions();

        $metrics = TrainingMetrics::fromSessions($paceZoneSessions);
        $paceStats = ZoneStatistics::fromSessions($paceZoneSessions, PaceZone::cases());
        $hrStats = ZoneStatistics::fromSessions($heartRateZoneSessions, HeartRateZone::cases());

        // Calculate speed zones for bikes
        $speedStats = $activityData->getSpeedZoneStatistics();
        $speedStatsFormatted = [];
        foreach ($speedStats as $stat) {
            $speedStatsFormatted[] = [
                'zone' => $stat['zone'],
                'duration' => $stat['duration'],
                'duration_percentage' => $stat['duration_percentage'],
            ];
        }

        $speedZoneSessions = $activityData->getSpeedZoneSessions();

        return $this->render('activity/analysis.html.twig', [
            'activityData' => $activityData,
            'metaData' => $metaData,
            'metrics' => $metrics,
            'paceZoneSessions' => $paceZoneSessions,
            'heartRateZoneSessions' => $heartRateZoneSessions,
            'paceStats' => $paceStats->toArray($activity->getMovingTimeSeconds()),
            'hrStats' => $hrStats->toArray($activity->getMovingTimeSeconds()),
            'speedStats' => $speedStatsFormatted,
            'speedZoneSessions' => $speedZoneSessions,
        ]);
    }
}
