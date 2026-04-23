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
    public function yieldDataPoints(): \Generator
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
