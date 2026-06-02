<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Activity as ActivityEntity;
use App\Strava\ActivityRepositoryInterface;
use App\Strava\API\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityEntity>
 */
final class ActivityRepository extends ServiceEntityRepository implements ActivityRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityEntity::class);
    }

    public function findOrNull(int $stravaUserId, int $stravaActivityId): ?Activity
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

    public function findLastOrNull(int $stravaUserId): ?Activity
    {
        $entity = $this->findOneBy(
            ['stravaUserId' => $stravaUserId],
            ['startDate' => 'DESC']
        );

        if ($entity instanceof ActivityEntity) {
            return self::fromEntity($entity);
        }

        return null;
    }

    public function persist(int $stravaUserId, Activity $activity): void
    {
        $existing = $this->findOrNull($stravaUserId, $activity->getId());
        assert(is_null($existing), sprintf('Already persisted %s!', $activity));

        $entity = ActivityEntity::fromActivity($stravaUserId, $activity);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    /** @return Activity[] */
    public function findByStravaUser(int $stravaUserId): array
    {
        /** @var ActivityEntity[] $entities */
        $entities = $this->findBy(['stravaUserId' => $stravaUserId], ['startDate' => 'DESC']);

        return array_map(self::fromEntity(...), $entities);
    }

    /**
     * Get recent activities for dashboard display.
     *
     * @return ActivityEntity[] Recent activities sorted by start date
     */
    public function findRecentByStravaUser(int $stravaUserId, int $limit = 20): array
    {
        /** @var ActivityEntity[] $entities */
        $entities = $this->createQueryBuilder('a')
            ->where('a.stravaUserId = :userId')
            ->setParameter('userId', $stravaUserId)
            ->orderBy('a.startDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $entities;
    }

    private static function fromEntity(ActivityEntity $entity): Activity
    {
        return Activity::fromRawData($entity->getData());
    }
}
