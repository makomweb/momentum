<?php

declare(strict_types=1);

namespace App\RunForge;

final readonly class ActivityAnalysisData
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
        public bool $hasVelocityData,
        public bool $hasHeartRateData,
        public int $movingAveragePeriod,
        public AnalysisMetadata $metadata,
    ) {
    }

    public function hasAnyData(): bool
    {
        return $this->hasVelocityData || $this->hasHeartRateData;
    }
}
