<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ActivityEntity;
use App\Strava\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityEntity>
 */
final class ActivityEntityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityEntity::class);
    }

    public function findByStravaId(int $stravaUserId, int $stravaActivityId): ?ActivityEntity
    {
        return $this->findOneBy([
            'stravaUserId' => $stravaUserId,
            'stravaActivityId' => $stravaActivityId,
        ]);
    }

    public function persist(int $stravaUserId, Activity $activity): ActivityEntity
    {
        $existing = $this->findByStravaId($stravaUserId, $activity->getId());

        if ($existing instanceof ActivityEntity) {
            return $existing;
        }

        $entity = ActivityEntity::fromActivity($stravaUserId, $activity);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();

        return $entity;
    }

    /**
     * @return ActivityEntity[]
     */
    public function findByStravaUser(int $stravaUserId): array
    {
        return $this->findBy(['stravaUserId' => $stravaUserId], ['createdAt' => 'DESC']);
    }
}
