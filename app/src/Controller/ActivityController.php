<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\AthleteInterface;
use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use App\Momentum\TrainingMetrics;
use App\Momentum\ZoneSession;
use App\Momentum\ZoneSessions;
use App\Momentum\ZoneStatistics;
use App\Repository\ActivityRepository;
use App\Strava\ActivityData;
use App\Strava\ActivityMetaData;
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

        $data = $this->createActivityData($activity, $stream);

        return $this->render('activity/analysis.html.twig', $data);
    }

    /**
     * @return array{
     *     activityData: ActivityData,
     *     metaData: ActivityMetaData,
     *     metrics: TrainingMetrics,
     *     paceZoneSessions: array<ZoneSession>,
     *     heartRateZoneSessions: array<ZoneSession>,
     *     paceStats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     *     hrStats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     * }
     */
    private function createActivityData(Activity $activity, ActivityStream $stream): array
    {
        $activityData = new ActivityData($activity, $stream);
        $metaData = $activityData->getMetaData();

        $sessions = self::createZoneSessions($this->athlete, $activity, $stream);
        $heartRateZoneSessions = $sessions->getHeartRateSessions();
        $paceZoneSessions = $sessions->getPaceZoneSessions();

        $metrics = TrainingMetrics::fromSessions($paceZoneSessions);
        $paceStats = ZoneStatistics::fromSessions($paceZoneSessions, PaceZone::cases());
        $hrStats = ZoneStatistics::fromSessions($heartRateZoneSessions, HeartRateZone::cases());

        // Option C: Use zone session totals instead of moving_time
        // This ensures percentages always aggregate to 100%
        $totalPaceSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $paceZoneSessions));
        $totalHrSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $heartRateZoneSessions));

        $paceStatsArray = $paceStats->toArray($totalPaceSeconds);
        $hrStatsArray = $hrStats->toArray($totalHrSeconds);

        return [
            'profile_picture' => $this->athlete->getProfilePicture(),
            'activityData' => $activityData,
            'metaData' => $metaData,
            'metrics' => $metrics,
            'paceZoneSessions' => $paceZoneSessions,
            'paceStats' => $paceStatsArray,
            'heartRateZoneSessions' => $heartRateZoneSessions,
            'hrStats' => $hrStatsArray,
        ];
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
}
