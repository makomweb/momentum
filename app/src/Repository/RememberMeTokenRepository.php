<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\RememberMeToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RememberMeToken>
 */
final class RememberMeTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RememberMeToken::class);
    }

    public function save(RememberMeToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function delete(RememberMeToken $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySelector(string $selector): ?RememberMeToken
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    /** @return RememberMeToken[] */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    public function deleteExpired(): int
    {
        $now = new \DateTimeImmutable();

        $result = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :now')
            ->setParameter('now', $now)
            ->getQuery()
            ->execute();

        return is_int($result) ? $result : 0;
    }

    public function deleteByUser(User $user): int
    {
        $result = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();

        return is_int($result) ? $result : 0;
    }
}
