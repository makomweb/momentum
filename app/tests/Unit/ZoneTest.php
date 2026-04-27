<?php

namespace App\Tests\Unit;

use App\Momentum\Period;
use App\Momentum\Zone;
use App\Strava\ActivityData;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use DateTimeImmutable;
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
            'average_speed' => 3.326
        ];

        $filePath = __DIR__.'/../data/activity_stream_14417573_18239981344.json';

        $activity = new ActivityData(
            Activity::fromRawData($data),
            ActivityStream::fromJsonFile($filePath)
        );

        $actual = [ /* TODO: use activities to determine a sequence of Period instances */ ];

        $expected = [
            new Period(
                DateTimeImmutable::createFromFormat('2026-04-26T16:23:30 +0200'),
                DateTimeImmutable::createFromFormat('2026-04-26T16:33:30 +0200'),
                Zone::TEMPO
            ),
            new Period(
                DateTimeImmutable::createFromFormat('2026-04-26T16:33:30 +0200'),
                DateTimeImmutable::createFromFormat('2026-04-26T16:53:30 +0200'),
                Zone::LONG_INTERVAL
            ),
            new Period(
                DateTimeImmutable::createFromFormat('2026-04-26T16:53:30 +0200'),
                DateTimeImmutable::createFromFormat('2026-04-26T17:02:30 +0200'),
                Zone::MODERATE
            ),
        ];

        self::assertEquals($expected, $actual);
    }
}
