<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\Power;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CurrentUser implements AthleteInterface
{
    public function __construct(private Security $security)
    {
    }

    public function getId(): int
    {
        return $this->getUser()->getStravaId();
    }

    public function getName(): string
    {
        return $this->getUser()->getName();
    }

    public function isConfigured(): bool
    {
        return !$this->getRunTreshold()->isZero()
            && !$this->getCyclingTreshold()->isZero()
            && $this->getMaxHeartRate()->isValid();
    }

    public function getMaxHeartRate(): HeartRate
    {
        return $this->getUser()->getMaxHeartRate();
    }

    public function getRunTreshold(): Pace
    {
        return $this->getUser()->getRunTreshold();
    }

    public function getCyclingTreshold(): Power
    {
        return $this->getUser()->getCyclingTreshold();
    }

    public function getProfilePicture(): string
    {
        return $this->getUser()->getProfilePictureMedium();
    }

    /**
     * @return array{
     *  strava_user_id: int,
     *  name: string,
     *  run_treshold: Pace,
     *  cycling_treshold: Power,
     *  max_heart_rate: HeartRate,
     *  profile_picture: string
     * }
     */
    public function toArray(): array
    {
        return [
            'strava_user_id' => $this->getId(),
            'name' => $this->getName(),
            'run_treshold' => $this->getRunTreshold(),
            'cycling_treshold' => $this->getCyclingTreshold(),
            'max_heart_rate' => $this->getMaxHeartRate(),
            'profile_picture' => $this->getProfilePicture(),
            'is_configured' => $this->isConfigured(),
        ];
    }

    private function getUser(): User
    {
        $user = $this->security->getUser();

        assert($user instanceof User, 'Please login first!');

        return $user;
    }
}
