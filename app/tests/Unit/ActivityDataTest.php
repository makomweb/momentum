<?php

namespace App\Tests\Unit;

use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Power;
use App\Strava\ActivityData;
use App\Strava\ActivityStreamFactory;
use App\Strava\API\Activity;
use App\Strava\Serializer\ActivityStreamDenormalizer;
use App\Strava\Serializer\SeriesDenormalizer;
use App\Twig\FormatMovingTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class ActivityDataTest extends TestCase
{
    use AthleteFactory;

    #[Test]
    public function format_moving_time_should_work(): void
    {
        $data = self::createActivityData(14417573, 18155703383);

        self::assertInstanceOf(ActivityData::class, $data);

        self::assertEquals('12 min 0 s', FormatMovingTime::format($data->getMetaData()->getMovingTime()));
    }

    #[Test]
    public function should_contain_watts(): void
    {
        $data = self::createActivityData(14417573, 19881882510);

        self::assertInstanceOf(ActivityData::class, $data);

        $wattData = $data->getWattPoints();

        self::assertNotEmpty($data->getWattPoints());

        $nonZeros = array_filter(
            $wattData,
            static fn (array $dataPoint) => $dataPoint['value'] > 0
        );

        self::assertNotEmpty($nonZeros, 'There should be power data!');
    }

    private static function createActivityData(int $athleteId, int $activityId): ActivityData
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

        $filePath = sprintf('%s/../data/activity_stream_%d_%d.json', __DIR__, $athleteId, $activityId);

        $factory = new ActivityStreamFactory(
            new Serializer(
                [
                    new ActivityStreamDenormalizer(new SeriesDenormalizer()),
                    new SeriesDenormalizer(),
                ],
                [
                    new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
                ]
            )
        );

        return new ActivityData(
            self::createAthlete(new Pace(5.00, PaceUnit::MINUTES_PER_KILOMETER), Power::ZERO(), new HeartRate(180)),
            Activity::fromRawData($data),
            $factory->fromJsonFile($filePath)
        );
    }
}
