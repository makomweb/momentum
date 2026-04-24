<?php

namespace App\Tests\Unit;

use App\Strava\ActivityData;
use App\Strava\API\Activity;
use App\Strava\API\ActivityStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActivityDataTest extends TestCase
{
    #[Test]
    public function duration_is_properly_formatted(): void
    {
        $data = self::createActivityData();

        self::assertInstanceOf(ActivityData::class, $data);

        self::assertEquals('00:12:00', $data->getMetaData()->getElapsedTimeAsString());
    }

    private static function createActivityData(): ActivityData
    {
        $data = [
            'id' => 4419625548,
            'name' => 'Coopertest',
            'type' => 'Run',
            'distance' => 2700,
            'timezone' => '(GMT+01:00) Europe/Berlin',
            'max_speed' => 0,
            'start_date' => '2020-12-02T10:08:09Z',
            'device_name' => false,
            'moving_time' => 720,
            'elapsed_time' => 720,
            'average_speed' => 3.75,
        ];

        $filePath = __DIR__.'/../data/activity_stream_14417573_18155703383.json';

        return new ActivityData(
            Activity::fromRawData($data),
            ActivityStream::fromJsonFile($filePath)
        );
    }
}
