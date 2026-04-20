<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activity_sync_state')]
#[ORM\UniqueConstraint(name: 'unique_strava_user_id', columns: ['strava_user_id'])]
final class ActivitySyncState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'integer')]
    private readonly int $stravaUserId;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastSyncAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $lastActivityTimestamp;

    #[ORM\Column(type: 'datetime_immutable')]
    private readonly \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(int $stravaUserId)
    {
        $this->stravaUserId = $stravaUserId;
        $this->lastSyncAt = null;
        $this->lastActivityTimestamp = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function create(int $stravaUserId): self
    {
        return new self($stravaUserId);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStravaUserId(): int
    {
        return $this->stravaUserId;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function getLastActivityTimestamp(): ?int
    {
        return $this->lastActivityTimestamp;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markSynced(int $mostRecentActivityTimestamp): void
    {
        $this->lastSyncAt = new \DateTimeImmutable();
        $this->lastActivityTimestamp = $mostRecentActivityTimestamp;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function hasNeverSynced(): bool
    {
        return null === $this->lastSyncAt;
    }
}
