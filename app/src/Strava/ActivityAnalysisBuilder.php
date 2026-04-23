<?php

declare(strict_types=1);

namespace App\Strava;

use App\RunForge\ActivityAnalysisData;
use App\RunForge\AnalysisMetadata;
use App\Strava\API\Activity as StravaActivity;
use App\Strava\API\ActivityStream as StravaActivityStream;

final class ActivityAnalysisBuilder
{
    private const MOVING_AVERAGE_PERIOD = 50;

    public function buildFromStream(
        StravaActivity $activity,
        StravaActivityStream $stream,
    ): ActivityAnalysisData {
        $activityName = $activity->getName();
        $distance = $activity->data['distance'];
        $elapsedTime = $activity->data['elapsed_time'];
        $startDate = $activity->getStartDateLocalTime();
        $activityType = $activity->data['type'];

        $velocityRawPoints = [];
        $velocityAveragePoints = [];
        $heartRatePoints = [];

        $hasVelocityData = false;
        $hasHeartRateData = false;

        // Check if we have time data (required for both)
        if (!$stream->hasTimeData()) {
            $metadata = AnalysisMetadata::fromActivityType($activityType, $activity->getSpeed());
            return new ActivityAnalysisData(
                activityName: $activityName,
                distance: $distance,
                elapsedTime: $elapsedTime,
                velocityRawPoints: [],
                velocityAveragePoints: [],
                heartRatePoints: [],
                hasVelocityData: false,
                hasHeartRateData: false,
                movingAveragePeriod: self::MOVING_AVERAGE_PERIOD,
                metadata: $metadata,
            );
        }

        // Use the yield() method which handles moving averages internally
        foreach ($stream->yield($startDate, self::MOVING_AVERAGE_PERIOD) as $point) {
            $timestamp = $point['time_stamp']->format('H:i:s');

            // Collect all raw velocity points if available
            if ($stream->hasVelocityData()) {
                $velocityRawPoints[] = [
                    'timestamp' => $timestamp,
                    'value' => round($point['velocity'], 2),
                ];

                // Collect all average points (simple MA during warmup, exponential MA after)
                $velocityAveragePoints[] = [
                    'timestamp' => $timestamp,
                    'value' => round($point['velocity_avg'], 2),
                ];
                $hasVelocityData = true;
            }

            // Collect heart rate points if available
            if ($stream->hasHeartRateData()) {
                $heartRatePoints[] = [
                    'timestamp' => $timestamp,
                    'value' => round($point['heart_rate'], 1),
                ];
                $hasHeartRateData = true;
            }
        }

        $metadata = AnalysisMetadata::fromActivityType($activityType, $activity->getSpeed());

        return new ActivityAnalysisData(
            activityName: $activityName,
            distance: $distance,
            elapsedTime: $elapsedTime,
            velocityRawPoints: $velocityRawPoints,
            velocityAveragePoints: $velocityAveragePoints,
            heartRatePoints: $heartRatePoints,
            hasVelocityData: $hasVelocityData,
            hasHeartRateData: $hasHeartRateData,
            movingAveragePeriod: self::MOVING_AVERAGE_PERIOD,
            metadata: $metadata,
        );
    }
}
