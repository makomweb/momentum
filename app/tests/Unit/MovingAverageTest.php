<?php

namespace App\Tests\Unit;

use App\Strava\ActivityStreamFactory;
use App\Strava\API\ActivityStream;
use App\Strava\Serializer\ActivityStreamDenormalizer;
use App\Strava\Serializer\SeriesDenormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

class MovingAverageTest extends TestCase
{
    private ActivityStream $stream;

    public function setUp(): void
    {
        $filePath = __DIR__.'/../data/activity_stream_14417573_19881882510.json';

        $factory = new ActivityStreamFactory($this->getSerializer());
        $stream = $factory->fromJsonFile($filePath);

        self::assertTrue($stream->hasVelocityData());
        self::assertTrue($stream->hasHeartRateData());
        self::assertTrue($stream->hasTimeData());
        self::assertEquals(7120, $stream->velocities->getCount());
        self::assertTrue($stream->hasWattData());

        $this->stream = $stream;
    }

    private function getSerializer(): Serializer
    {
        return new Serializer(
            [
                new ActivityStreamDenormalizer(new SeriesDenormalizer()),
                new SeriesDenormalizer(),
            ],
            [
                new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            ]
        );
    }

    #[Test]
    public function calculate_moving_average_speed(): void
    {
        $start = new \DateTimeImmutable();

        $items = array_values(iterator_to_array($this->stream->yield($start, movingAveragePeriod: 10)));
        assert(!empty($items));

        $last = array_last($items);
        $velocity = $last->getVelocityAverage();

        self::assertGreaterThan(1, $velocity->metersPerSecond);
        self::assertLessThan(15, $velocity->metersPerSecond);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $last->getTimestamp()
            )->days
        );
    }

    #[Test]
    public function calculate_moving_average_heart_rate(): void
    {
        $start = new \DateTimeImmutable();

        $items = array_values(iterator_to_array($this->stream->yield($start, movingAveragePeriod: 10)));
        assert(!empty($items));

        $last = array_last($items);
        $heartRateAvg = $last->getHeartRateAverage();

        self::assertGreaterThan(60.0, $heartRateAvg->bpm);
        self::assertLessThan(180.0, $heartRateAvg->bpm);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $last->getTimestamp()
            )->days
        );
    }

    #[Test]
    public function yield_moving_averages(): void
    {
        $start = new \DateTimeImmutable();
        $series = iterator_to_array(
            $this->stream->yield($start, 10)
        );

        self::assertCount(7120, $series);

        foreach ($series as $data) {
            $timestamp = $data->getTimestamp();
            self::assertEquals(
                0,
                date_diff($start, $timestamp)->days
            );
            self::assertGreaterThanOrEqual($start, $timestamp);

            $distance = $data->getDistance();
            self::assertGreaterThanOrEqual(0.0, $distance);

            $heartRate = $data->getHeartRate();
            self::assertGreaterThan(60.0, $heartRate->bpm);
            self::assertLessThan(180.0, $heartRate->bpm);

            $heartRateAvg = $data->getHeartRateAverage();
            self::assertGreaterThanOrEqual(0.0, $heartRateAvg->bpm);
            self::assertLessThan(180.0, $heartRateAvg->bpm);

            $velocity = $data->getVelocity();
            self::assertGreaterThanOrEqual(0.0, $velocity->metersPerSecond);
            self::assertLessThan(50.0, $velocity->metersPerSecond);

            $velocityAvg = $data->getVelocityAverage();
            self::assertGreaterThanOrEqual(0.0, $velocityAvg->metersPerSecond);
            self::assertLessThan(50.0, $velocityAvg->metersPerSecond);
        }
    }
}
