<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class AnalysisPresenter
{
    private const MOVING_AVERAGE_PERIOD = 50;

    public function buildAnalysis(ActivityData $activityData, ActivityMetaData $metaData): AnalysisData
    {
        $velocityRawPoints = [];
        $velocityAveragePoints = [];
        $heartRatePoints = [];

        // Process data points
        foreach ($activityData->getDataPoints() as $point) {
            // Collect velocity points (already converted to correct units)
            if ($point['velocity'] > 0 || $point['velocity_avg'] > 0) {
                $velocityRawPoints[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['velocity'], 2),
                ];
                $velocityAveragePoints[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['velocity_avg'], 2),
                ];
            }

            // Collect heart rate points (prefer average)
            if ($point['heart_rate'] > 0 || $point['heart_rate_avg'] > 0) {
                $heartRatePoints[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['heart_rate_avg'] ?: $point['heart_rate'], 1),
                ];
            }
        }

        // Build metadata
        $analysisMetadata = AnalysisMetadata::fromActivityMetaData($metaData);

        // Extract distance and elapsed time from metaData
        $data = $metaData->getData();
        $distance = $data['distance']->getMeters();
        $dateInterval = $data['elapsed_time'];
        $elapsedTime = (int) (
            $dateInterval->d * 86400 +
            $dateInterval->h * 3600 +
            $dateInterval->i * 60 +
            $dateInterval->s
        );

        return new AnalysisData(
            activityName: $metaData->getActivityName(),
            distance: $distance,
            elapsedTime: $elapsedTime,
            velocityRawPoints: $velocityRawPoints,
            velocityAveragePoints: $velocityAveragePoints,
            heartRatePoints: $heartRatePoints,
            movingAveragePeriod: self::MOVING_AVERAGE_PERIOD,
            metadata: $analysisMetadata,
            startDateLocalTime: $metaData->getStartDateLocalTime(),
        );
    }
}
