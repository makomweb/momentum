<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class AnalysisData
{
    /**
     * @param array<int, array{timestamp: string, value: float}> $velocityRawPoints
     * @param array<int, array{timestamp: string, value: float}> $velocityAveragePoints
     * @param array<int, array{timestamp: string, value: float}> $heartRatePoints
     */
    public function __construct(
        public string $activityName,
        public float $distance,
        public int $elapsedTime,
        public array $velocityRawPoints,
        public array $velocityAveragePoints,
        public array $heartRatePoints,
        public int $movingAveragePeriod,
        public AnalysisMetadata $metadata,
        public \DateTimeImmutable $startDateLocalTime,
    ) {
    }

    public function hasVelocityData(): bool
    {
        return !empty($this->velocityRawPoints) && !empty($this->velocityAveragePoints);
    }

    public function hasHeartRateData(): bool
    {
        return !empty($this->heartRatePoints);
    }

    public function hasAnyData(): bool
    {
        return $this->hasVelocityData() || $this->hasHeartRateData();
    }
}
