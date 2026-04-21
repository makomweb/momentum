<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    /** @phpstan-ignore property.onlyRead */
    private int $id;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $stravaId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $athleteName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct(
        int $stravaId,
        ?string $athleteName = null,
        ?string $email = null,
    ) {
        $this->stravaId = $stravaId;
        $this->athleteName = $athleteName;
        $this->email = $email;
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStravaId(): int
    {
        return $this->stravaId;
    }

    public function getAthleteName(): ?string
    {
        return $this->athleteName;
    }

    public function setAthleteName(?string $athleteName): void
    {
        $this->athleteName = $athleteName;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return (string) $this->stravaId;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        // No password for OAuth-only users
        return null;
    }

    public function getSalt(): null
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase for OAuth users
    }

    public function __toString(): string
    {
        return sprintf('%s (Strava ID: %d)', $this->athleteName ?? 'User', $this->stravaId);
    }
}
