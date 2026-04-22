<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class ActivityAnalysisData
{
    /**
     * @param array<int, array{timestamp: string, value: float}> $velocityPoints
     * @param array<int, array{timestamp: string, value: float}> $heartRatePoints
     */
    public function __construct(
        public string $activityName,
        public float $distance,
        public int $elapsedTime,
        public array $velocityPoints,
        public array $heartRatePoints,
        public bool $hasVelocityData,
        public bool $hasHeartRateData,
    ) {
    }

    public function hasAnyData(): bool
    {
        return $this->hasVelocityData || $this->hasHeartRateData;
    }
}
