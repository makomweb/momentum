<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\ActivityShareToken;
use App\Momentum\ActivityShareTokenException;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\TrainingMetrics;
use App\Momentum\ZoneSession;
use App\Repository\ActivityRepository;
use App\Repository\UserRepository;
use App\Strava\ActivityData;
use App\Strava\ActivityMetaData;
use App\Strava\ActivityStreamCache;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/activity', name: 'activity_')]
final class ActivityController extends AbstractController
{
    public function __construct(
        private ActivityRepository $activityRepository,
        private ActivityStreamCache $streamCache,
        private ActivityShareToken $shareToken,
        private AthleteInterface $athlete,
        private UserRepository $users,
    ) {
    }

    #[Route('/{id}', name: 'analyze', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function analyze(int $id): Response
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
                'activity_id' => $activity->getId(),
                'activity_name' => $activity->getName(),
                'profile_picture' => $this->athlete->getProfilePicture(),
            ]);
        }

        if (!$stream->hasData()) {
            return $this->render('activity/analysis_no_stream.html.twig', [
                'activity_id' => $activity->getId(),
                'activity_name' => $activity->getName(),
                'profile_picture' => $this->athlete->getProfilePicture(),
            ]);
        }

        $data = self::createAnalysisData($this->athlete, $activity, $stream);

        return $this->render('activity/analysis.html.twig', $data);
    }

    #[Route('/{id}/share', name: 'share', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function share(int $id, Request $request): JsonResponse
    {
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

        $shareData = $this->shareToken->generate($this->athlete->getStravaId(), $activity->getId(), $expiryDays);

        return $this->json([
            ...$shareData,
            'share_url' => $this->generateUrl('activity_view', ['token' => $shareData['token']], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);
    }

    #[Route('/view/{token}', name: 'view', methods: ['GET'], requirements: ['token' => '[a-z0-9:.]+'])]
    public function view(string $token): Response
    {
        try {
            $result = $this->shareToken->validate($token);
        } catch (ActivityShareTokenException $e) {
            return $this->render('share/expired.html.twig', [
                'message' => $e->getMessage(),
            ], new Response(status: Response::HTTP_GONE));
        }

        $stravaUserId = $result['strava_user_id'];
        $stravaActivityId = $result['strava_activity_id'];

        $activity = $this->activityRepository->findOrNull($stravaUserId, $stravaActivityId);

        if (null === $activity) {
            return $this->render('share/not_found.html.twig', [], new Response(status: Response::HTTP_NOT_FOUND));
        }

        try {
            $stream = $this->streamCache->get(
                $stravaUserId,
                $activity->getId()
            );
        } catch (\Exception) {
            return $this->render('share/no_stream.html.twig', [
                'activity_name' => $activity->getName(),
            ]);
        }

        if (!$stream->hasData()) {
            return $this->render('share/no_stream.html.twig', [
                'activity_name' => $activity->getName(),
            ]);
        }

        $user = $this->users->findByStravaId($stravaUserId);
        assert($user instanceof User);

        $athlete = new class($user) implements AthleteInterface {
            public function __construct(private User $user)
            {
            }

            public function getStravaId(): int
            {
                throw new \Exception('Not implemented');
            }

            public function getName(): string
            {
                return $this->user->getName();
            }

            public function getTreshold(): Pace
            {
                return $this->user->getTreshold();
            }

            public function getMaxHeartRate(): HeartRate
            {
                return $this->user->getMaxHeartRate();
            }

            public function getProfilePicture(): string
            {
                return $this->user->getProfilePictureMedium();
            }

            public function isConfigured(): bool
            {
                throw new \Exception('Not implemented');
            }

            public function toArray(): array
            {
                throw new \Exception('Not implemented');
            }
        };

        $analysisData = self::createAnalysisData($athlete, $activity, $stream);

        $data = [
            ...$activity->data,
            'moving_time' => $activity->getMovingTime(),
            'average_velocity' => $activity->getAverageVelocityAsStringable(),
            'start_date' => $activity->getStartDateLocalTime(),
            'meta_data' => $analysisData['meta_data'],
            'hr_stats' => $analysisData['hr_stats'],
            'pace_stats' => $analysisData['pace_stats'],
            'user' => $user,
        ];

        return $this->render('share/activity.html.twig', $data);
    }

    /**
     * @return array{
     *     activity_id: int,
     *     meta_data: ActivityMetaData,
     *     metrics: TrainingMetrics,
     *     pace_zone_sessions: array<ZoneSession>,
     *     heart_rate_zone_sessions: array<ZoneSession>,
     *     pace_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     *     hr_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     * }
     */
    private static function createAnalysisData(AthleteInterface $athlete, Activity $activity, ActivityStream $stream): array
    {
        $activityData = new ActivityData($athlete, $activity, $stream);

        return [
            'activity_id' => $activity->getId(),
            'profile_picture' => $athlete->getProfilePicture(),
            ...$activityData->toArray(),
        ];
    }
}
