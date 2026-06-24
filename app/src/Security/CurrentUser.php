<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class CurrentUser implements AthleteInterface
{
    public function __construct(private Security $security)
    {
    }

    public function getStravaId(): int
    {
        return $this->getUser()->getStravaId();
    }

    public function getName(): string
    {
        return $this->getUser()->getName();
    }

    public function isConfigured(): bool
    {
        return !$this->getTreshold()->isZero() && $this->getMaxHeartRate()->isValid();
    }

    public function getMaxHeartRate(): HeartRate
    {
        return $this->getUser()->getMaxHeartRate();
    }

    public function getTreshold(): Pace
    {
        return $this->getUser()->getTreshold();
    }

    public function getProfilePicture(): string
    {
        return $this->getUser()->getProfilePictureMedium();
    }

    /**
     * @return array{
     *  strava_user_id: int,
     *  name: string,
     *  treshold: Pace,
     *  max_heart_rate: HeartRate,
     *  profile_picture: string
     * }
     */
    public function toArray(): array
    {
        return [
            'strava_user_id' => $this->getStravaId(),
            'name' => $this->getName(),
            'treshold' => $this->getTreshold(),
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
