<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ActivitySyncState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivitySyncState>
 */
final class ActivitySyncStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivitySyncState::class);
    }

    public function findOrCreateForUser(int $stravaUserId): ActivitySyncState
    {
        $state = $this->findOneBy(['stravaUserId' => $stravaUserId]);

        if ($state instanceof ActivitySyncState) {
            return $state;
        }

        $state = ActivitySyncState::create($stravaUserId);
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();

        return $state;
    }

    public function save(ActivitySyncState $state): void
    {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }
}
