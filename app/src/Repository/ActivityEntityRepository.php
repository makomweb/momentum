<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ActivityEntity;
use App\Strava\ActivityRepositoryInterface;
use App\Strava\API\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityEntity>
 */
final class ActivityEntityRepository extends ServiceEntityRepository implements ActivityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityEntity::class);
    }

    public function findByStravaId(int $stravaUserId, int $stravaActivityId): ?Activity
    {
        $entity = $this->findOneBy([
            'stravaUserId' => $stravaUserId,
            'stravaActivityId' => $stravaActivityId,
        ]);

        if ($entity instanceof ActivityEntity) {
            return self::fromEntity($entity);
        }

        return null;
    }

    public function persist(int $stravaUserId, Activity $activity): void
    {
        $existing = $this->findByStravaId($stravaUserId, $activity->getId());

        if ($existing instanceof Activity) {
            throw new \Exception(sprintf('Activity with ID "%d" already present!', $activity->getId()));
        }

        $entity = ActivityEntity::fromActivity($stravaUserId, $activity);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /** @return Activity[] */
    public function findByStravaUser(int $stravaUserId): array
    {
        /** @var ActivityEntity[] $entities */
        $entities = $this->findBy(['stravaUserId' => $stravaUserId], ['createdAt' => 'DESC']);

        return array_map(self::fromEntity(...), $entities);
    }

    private static function fromEntity(ActivityEntity $entity): Activity
    {
        return new Activity(
            data: [
                'id' => $entity->getStravaActivityId(),
                'device_name' => $entity->getDeviceName(),
                'name' => $entity->getName(),
                'start_date' => $entity->getStartDate(),
                'timezone' => $entity->getTimezone(),
                'elapsed_time' => $entity->getElapsedTime(),
                'moving_time' => $entity->getMovingTime(),
                'distance' => $entity->getDistance(),
                'max_speed' => $entity->getMaxSpeed(),
                'average_speed' => $entity->getAverageSpeed(),
                'type' => $entity->getType(),
            ]
        );
    }
}
