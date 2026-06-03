<?php

namespace App\Tests\Unit;

use App\Strava\ActivityData;
use App\Strava\ActivityStreamFactory;
use App\Strava\API\Activity;
use App\Strava\Serializer\ActivityStreamDenormalizer;
use App\Strava\Serializer\SeriesDenormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class ActivityDataTest extends TestCase
{
    #[Test]
    public function moving_time_is_properly_formatted(): void
    {
        $data = self::createActivityData();

        self::assertInstanceOf(ActivityData::class, $data);

        self::assertEquals('00h 12min 00s', $data->getMetaData()->getMovingTimeAsString());
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

        $factory = new ActivityStreamFactory(
            new Serializer(
                [
                    new ActivityStreamDenormalizer(),
                    new SeriesDenormalizer(),
                ],
                [
                    new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                ]
            )
        );

        return new ActivityData(
            Activity::fromRawData($data),
            $factory->fromJsonFile($filePath)
        );
    }
}
