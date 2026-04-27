<?php

namespace App\Tests\Unit;

use App\Momentum\Zone;
use App\Strava\ActivityData;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use App\Strava\ZoneDetector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ZoneTest extends TestCase
{
    #[Test]
    public function determine_zones(): void
    {
        $data = [
            'id' => 18239981344,
            'name' => 'Schwelle',
            'type' => 'Run',
            'distance' => 7966.9,
            'timezone' => '(GMT+01:00) Europe/Berlin',
            'max_speed' => 4.5,
            'start_date' => '2026-04-24T14:23:00Z',
            'device_name' => 'Garmin Forerunner 970',
            'moving_time' => 2395,
            'elapsed_time' => 2402,
            'average_speed' => 3.326,
        ];

        $filePath = __DIR__.'/../data/activity_stream_14417573_18239981344.json';

        $activity = new ActivityData(
            Activity::fromRawData($data),
            ActivityStream::fromJsonFile($filePath)
        );

        $actual = (new ZoneDetector())->detect($activity);

        // Test expectations: 3 zones detected using combined velocity + heart rate thresholds
        self::assertCount(3, $actual);

        // First zone: MODERATE (warmup)
        self::assertEquals(Zone::MODERATE, $actual[0]->zone);
        self::assertGreaterThan(0, $actual[0]->start->getTimestamp());

        // Second zone: LONG_INTERVAL (main workout)
        self::assertEquals(Zone::LONG_INTERVAL, $actual[1]->zone);
        self::assertGreaterThanOrEqual($actual[0]->end->getTimestamp(), $actual[1]->start->getTimestamp());

        // Third zone: MODERATE (cooldown)
        self::assertEquals(Zone::MODERATE, $actual[2]->zone);
        self::assertGreaterThanOrEqual($actual[1]->end->getTimestamp(), $actual[2]->start->getTimestamp());
    }
}
