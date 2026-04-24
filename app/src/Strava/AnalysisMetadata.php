<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Velocity;

final readonly class AnalysisMetadata
{
    public function __construct(
        public string $emoji,
        public string $metricLabel,
        public string $chartTitle,
        public string $chartYAxisLabel,
        public Velocity $averageVelocity,
        public bool $usesInverseScale,
    ) {
    }

    public static function fromActivityMetaData(ActivityMetaData $metaData): self
    {
        $data = $metaData->getData();
        $averageVelocity = $data['average_velocity'];

        return new self(
            emoji: $metaData->getEmoji(),
            metricLabel: $metaData->getLabel(),
            chartTitle: $metaData->getChartTitle(),
            chartYAxisLabel: $averageVelocity->unit->value,
            averageVelocity: $averageVelocity,
            usesInverseScale: $metaData->useInverseScale(),
        );
    }
}
