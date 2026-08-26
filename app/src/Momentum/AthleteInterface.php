<?php

declare(strict_types=1);

namespace App\Momentum;

interface AthleteInterface
{
    public function getId(): int;

    public function getName(): string;

    public function getRunTreshold(): Pace;

    public function getCyclingTreshold(): Power;

    public function getMaxHeartRate(): HeartRate;

    public function getProfilePicture(): string;

    public function isConfigured(): bool;

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
    public function toArray(): array;
}
