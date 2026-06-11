<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
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

        return $this->renderResponse($user, $activity->getId());
    }

    #[Route('/{id}', name: 'analysis_by_id', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyzeActivityById(int $id): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->renderResponse($user, $id);
    }

    private function renderResponse(User $user, int $stravaActivityId): Response
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

        $data = self::createActivityData($user, $activity, $stream);

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
    private static function createActivityData(User $user, Activity $activity, ActivityStream $stream): array
    {
        $activityData = new ActivityData($activity, $stream);
        $metaData = $activityData->getMetaData();

        $sessions = self::createZoneSessions($user, $activity, $stream);
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
