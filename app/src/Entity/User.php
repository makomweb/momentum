<?php

declare(strict_types=1);

namespace App\Entity;

use App\Momentum\Pace;
use App\Strava\API\Athlete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    /** @phpstan-ignore property.onlyRead */
    private int $id;

    #[ORM\Column(type: 'bigint', unique: true)]
    private int $stravaId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $profilePicture;

    #[ORM\Column(type: 'string', length: 255)]
    private string $profilePictureMedium;

    #[ORM\Column]
    private float $ftp;

    #[ORM\Column]
    private int $maxHeartRate;

    private function __construct(
        int $stravaId,
        string $name,
        string $profilePicture,
        string $profilePictureMedium,
    ) {
        $this->stravaId = $stravaId;
        $this->name = $name;
        $this->profilePicture = $profilePicture;
        $this->profilePictureMedium = $profilePictureMedium;
        $this->ftp = 0.0;
        $this->maxHeartRate = 0;
    }

    public static function fromStravaUser(Athlete $athlete): self
    {
        return new self(
            $athlete->getId(),
            $athlete->getName(),
            $athlete->getProfile(),
            $athlete->getProfileMedium()
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStravaId(): int
    {
        return $this->stravaId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    // UserInterface implementation
    public function getUserIdentifier(): string
    {
        return sprintf('%s (Strava ID: %d)', $this->getName(), $this->getStravaId());
    }

    public function getProfilePicture(): string
    {
        return $this->profilePicture;
    }

    public function getProfilePictureMedium(): string
    {
        return $this->profilePictureMedium;
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
        return sprintf('%s (Strava ID: %d)', $this->name, $this->stravaId);
    }

    public function getFtp(): Pace
    {
        return new Pace($this->ftp);
    }

    public function setFtp(Pace $ftp): void
    {
        $this->ftp = $ftp->value;
    }

    public function getMaxHeartRate(): int
    {
        return $this->maxHeartRate;
    }

    public function setMaxHeartRate(int $maxHeartRate): void
    {
        $this->maxHeartRate = $maxHeartRate;
    }
}
