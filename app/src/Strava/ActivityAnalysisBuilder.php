<?php

declare(strict_types=1);

namespace App\Strava;

use App\RunForge\MovingAverage;
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
        $startDate = $activity->getStartDateUniversalTime();

        $velocityPoints = [];
        $heartRatePoints = [];

        // Check if we have time data (required for everything else)
        if (!$stream->hasTimeData()) {
            return new ActivityAnalysisData(
                activityName: $activityName,
                distance: $distance,
                elapsedTime: $elapsedTime,
                velocityPoints: [],
                heartRatePoints: [],
                hasVelocityData: false,
                hasHeartRateData: false,
            );
        }

        // Build velocity chart if data is available
        if ($stream->hasVelocityData()) {
            $velocityAvg = MovingAverage::create('velocity', $startDate, self::MOVING_AVERAGE_PERIOD);
            foreach ($stream->yieldSeries($startDate, 'velocity') as $point) {
                $velocityAvg = $velocityAvg->with($point['value'], $point['time_stamp']);
                // Only add after warmup period to avoid initial ramp
                if (count($velocityAvg->values) >= self::MOVING_AVERAGE_PERIOD) {
                    $velocityPoints[] = [
                        'timestamp' => $velocityAvg->getTimestamp(),
                        'value' => round($velocityAvg->getValue(), 2),
                    ];
                }
            }
        }

        // Build heart rate chart if data is available
        if ($stream->hasHeartRateData()) {
            $heartRateAvg = MovingAverage::create('heart_rate', $startDate, self::MOVING_AVERAGE_PERIOD);
            foreach ($stream->yieldSeries($startDate, 'heart_rate') as $point) {
                $heartRateAvg = $heartRateAvg->with($point['value'], $point['time_stamp']);
                // Only add after warmup period to avoid initial ramp
                if (count($heartRateAvg->values) >= self::MOVING_AVERAGE_PERIOD) {
                    $heartRatePoints[] = [
                        'timestamp' => $heartRateAvg->getTimestamp(),
                        'value' => round($heartRateAvg->getValue(), 2),
                    ];
                }
            }
        }

        return new ActivityAnalysisData(
            activityName: $activityName,
            distance: $distance,
            elapsedTime: $elapsedTime,
            velocityPoints: $velocityPoints,
            heartRatePoints: $heartRatePoints,
            hasVelocityData: !empty($velocityPoints),
            hasHeartRateData: !empty($heartRatePoints),
        );
    }
}
