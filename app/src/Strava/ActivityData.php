<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use App\Strava\API\Velocity;

final class ActivityData
{
    private const PERIOD = 50;

    public function __construct(private Activity $activity, private ActivityStream $stream)
    {
    }

    public function toAnalysisData(): AnalysisData
    {
        $velocityRawPoints = [];
        $velocityAveragePoints = [];
        $heartRatePoints = [];
        $hasVelocityData = false;
        $hasHeartRateData = false;

        foreach ($this->yieldDataPoints() as $point) {
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
                $hasVelocityData = true;
            }

            // Collect heart rate points (prefer average)
            if ($point['heart_rate'] > 0 || $point['heart_rate_avg'] > 0) {
                $heartRatePoints[] = [
                    'timestamp' => $point['timestamp'],
                    'value' => round($point['heart_rate_avg'] ?: $point['heart_rate'], 1),
                ];
                $hasHeartRateData = true;
            }
        }

        // Create metadata from activity type
        $averageVelocity = Velocity::fromStrava(
            $this->activity->data['average_speed'],
            $this->activity->data['type']
        );
        $metadata = AnalysisMetadata::fromActivityType(
            $this->activity->data['type'],
            $averageVelocity
        );

        return new AnalysisData(
            activityName: $this->activity->getName(),
            distance: $this->activity->data['distance'],
            elapsedTime: $this->activity->data['elapsed_time'],
            velocityRawPoints: $velocityRawPoints,
            velocityAveragePoints: $velocityAveragePoints,
            heartRatePoints: $heartRatePoints,
            hasVelocityData: $hasVelocityData,
            hasHeartRateData: $hasHeartRateData,
            movingAveragePeriod: self::PERIOD,
            metadata: $metadata,
            startDateLocalTime: $this->activity->getStartDateLocalTime(),
        );
    }

    /**
     * @return \Generator<array{
     *  timestamp: non-falsy-string,
     *  distance: float,
     *  heart_rate: int,
     *  heart_rate_avg: float,
     *  velocity: float,
     *  velocity_avg: float,
     * }>
     */
    private function yieldDataPoints(): \Generator
    {
        $activityType = $this->activity->data['type'];

        foreach ($this->stream->yield($this->activity->getStartDateLocalTime(), self::PERIOD) as $point) {
            $timestamp = $point['timestamp'];

            yield [
                'timestamp' => $timestamp->format('H:i:s'),
                'distance' => $point['distance'],
                'heart_rate' => $point['heart_rate'],
                'heart_rate_avg' => $point['heart_rate_avg'],
                'velocity' => Velocity::fromStrava($point['velocity'], $activityType)->value,
                'velocity_avg' => Velocity::fromStrava($point['velocity_avg'], $activityType)->value,
            ];
        }
    }
}
