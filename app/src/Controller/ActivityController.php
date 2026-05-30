<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\ActivityDataPoint;
use App\Momentum\AthleteInterface;
use App\Momentum\ZoneAnalysis;
use App\Momentum\ZoneDuration;
use App\Momentum\ZoneSessions;
use App\Momentum\ZoneTransitionTracker;
use App\Repository\ActivityRepository;
use App\Strava\ActivityData;
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

        $analysis = self::createAnalysis($user, $activity, $stream);

        $paceStats = $analysis->getPaceZoneStatistics();
        $hrStats = $analysis->getHeartRateZoneStatistics();
        $metrics = $analysis->getTrainingMetrics();

        return $this->json([
            'activity' => [
                'id' => $activity->getId(),
                'name' => $activity->getName(),
                'start_date' => $activity->getStartDateLocalTime()->format('c'),
            ],
            'pace_zones' => array_map(
                fn (ZoneDuration $zoneDuration) => $zoneDuration->toArray(),
                $paceStats->statistics
            ),
            'heart_rate_zones' => array_map(
                fn (ZoneDuration $zoneDuration) => $zoneDuration->toArray(),
                $hrStats->statistics
            ),
            'polarized_training' => [
                'easy_seconds' => $metrics->easySeconds,
                'easy_percentage' => round($metrics->getEasyPercentage(), 2),
                'medium_seconds' => $metrics->getMediumSeconds(),
                'medium_percentage' => round($metrics->getMediumPercentage(), 2),
                'hard_seconds' => $metrics->hardSeconds,
                'hard_percentage' => round($metrics->getHardPercentage(), 2),
            ],
        ]);
    }

    private static function createAnalysis(AthleteInterface $athlete, Activity $activity, ActivityStream $stream): ZoneAnalysis
    {
        $startDate = $activity->getStartDateLocalTime();

        /** @var ActivityDataPoint[] $dataPoints */
        $dataPoints = array_reduce(
            iterator_to_array($stream->yield($startDate, 50)),
            static fn (array $carry, ActivityDataPoint $p) => ($p->hasVelocity() && !$p->getVelocityAverage()->tooSlow()) ? [...$carry, $p] : $carry,
            []
        );

        $zoneSessions = ZoneSessions::create($athlete, $activity->getEndDateLocalTime(), $dataPoints);

        return new ZoneAnalysis(
            paceZoneSessions: $zoneSessions->getPaceZoneSessions(),
            heartRateZoneSessions: $zoneSessions->getHeartRateSessions(),
            totalSeconds: intval($activity->getMovingTime()->format('%s'))
        );
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
        $treshold = $user->getTreshold();
        assert(!$treshold->isZero(), 'Configure a treshold first!');

        $analysis = self::createAnalysis($user, $activity, $stream);

        return $this->render('activity/analysis.html.twig', [
            'activityData' => $activityData,
            'metaData' => $metaData,
            'zoneAnalysis' => $analysis,
        ]);
    }
}
