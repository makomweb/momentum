<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity as StravaActivity;
use App\Strava\API\ActivityStream;

final class ActivityAnalysisBuilder
{
    private const MOVING_AVERAGE_PERIOD = 10;

    public function buildFromStream(
        StravaActivity $activity,
        ActivityStream $stream,
    ): ActivityAnalysisData {
        $activityName = $activity->getName();
        $distance = $activity->data['distance'];
        $elapsedTime = $activity->data['elapsed_time'];
        $startDate = $activity->getStartDateLocalTime();

        $velocityRawPoints = [];
        $velocityAveragePoints = [];
        $heartRatePoints = [];

        // Check if we have all required data
        if (!$stream->hasTimeData() || !$stream->hasVelocityData() || !$stream->hasHeartRateData()) {
            return new ActivityAnalysisData(
                activityName: $activityName,
                distance: $distance,
                elapsedTime: $elapsedTime,
                velocityRawPoints: [],
                velocityAveragePoints: [],
                heartRatePoints: [],
                hasVelocityData: false,
                hasHeartRateData: false,
            );
        }

        // Use the yield() method which handles moving averages internally
        foreach ($stream->yield($startDate, self::MOVING_AVERAGE_PERIOD) as $point) {
            $timestamp = $point['time_stamp']->format('H:i:s');

            // Collect all raw velocity points
            $velocityRawPoints[] = [
                'timestamp' => $timestamp,
                'value' => round($point['velocity'], 2),
            ];

            // Collect all average points (simple MA during warmup, exponential MA after)
            $velocityAveragePoints[] = [
                'timestamp' => $timestamp,
                'value' => round($point['velocity_avg'], 2),
            ];

            // Collect heart rate points
            $heartRatePoints[] = [
                'timestamp' => $timestamp,
                'value' => round($point['heart_rate'], 1),
            ];
        }

        return new ActivityAnalysisData(
            activityName: $activityName,
            distance: $distance,
            elapsedTime: $elapsedTime,
            velocityRawPoints: $velocityRawPoints,
            velocityAveragePoints: $velocityAveragePoints,
            heartRatePoints: $heartRatePoints,
            hasVelocityData: !empty($velocityRawPoints),
            hasHeartRateData: !empty($heartRatePoints),
        );
    }
}
