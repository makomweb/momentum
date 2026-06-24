<?php

declare(strict_types=1);

namespace App\Momentum;

interface AthleteInterface
{
    public function getStravaId(): int;

    public function getName(): string;

    public function getTreshold(): Pace;

    public function getMaxHeartRate(): HeartRate;

    public function getProfilePicture(): string;

    public function isConfigured(): bool;

    /**
     * @return array{
     *  strava_user_id: int,
     *  name: string,
     *  treshold: Pace,
     *  max_heart_rate: HeartRate,
     *  profile_picture: string
     * }
     */
    public function toArray(): array;
}
