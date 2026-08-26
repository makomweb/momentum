<?php

declare(strict_types=1);

namespace App\Entity;

use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Power;
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
    private float $runTreshold;

    #[ORM\Column]
    private int $cyclingTreshold;

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
        $this->runTreshold = 0.0;
        $this->cyclingTreshold = 0;
        $this->maxHeartRate = 0;
    }

    public static function fromAthlete(Athlete $athlete): self
    {
        return new self(
            $athlete->id,
            $athlete->getName(),
            $athlete->profile,
            $athlete->profileMedium
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
        return (string) $this->getStravaId();
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

    public function getRunTreshold(): Pace
    {
        return new Pace($this->runTreshold, PaceUnit::MINUTES_PER_KILOMETER);
    }

    public function setRunTreshold(Pace $treshold): void
    {
        $this->runTreshold = $treshold->value;
    }

    public function getCyclingTreshold(): Power
    {
        return new Power($this->cyclingTreshold);
    }

    public function setCyclingTreshold(Power $treshold): void
    {
        $this->cyclingTreshold = $treshold->watt;
    }

    public function getMaxHeartRate(): HeartRate
    {
        return new HeartRate($this->maxHeartRate);
    }

    public function setMaxHeartRate(HeartRate $maxHeartRate): void
    {
        $this->maxHeartRate = $maxHeartRate->bpm;
    }
}
