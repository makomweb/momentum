<?php

namespace App\Tests\Unit;

use App\Strava\API\ActivityStream;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class MovingAverageTest extends TestCase
{
    private ActivityStream $stream;

    public function setUp(): void
    {
        $filePath = __DIR__.'/../data/activity_stream_14417573_18155703383.json';

        $stream = ActivityStream::fromJsonFile($filePath);

        self::assertTrue($stream->hasVelocityData());
        self::assertNotNull($stream->velocity);
        self::assertNotNull($stream->time);
        self::assertEquals(7887, $stream->velocity->getCount());

        $this->stream = $stream;
    }

    #[Test]
    public function calculate_moving_average_speed(): void
    {
        $start = new \DateTimeImmutable();

        $items = array_values(iterator_to_array($this->stream->yield($start, period: 10)));
        assert(!empty($items));

        $last = array_last($items);
        $velocity = $last['velocity_avg'];

        self::assertGreaterThan(1, $velocity->metersPerSecond);
        self::assertLessThan(15, $velocity->metersPerSecond);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $last['timestamp']
            )->days
        );
    }

    #[Test]
    public function calculate_moving_average_heart_rate(): void
    {
        $start = new \DateTimeImmutable();

        $items = array_values(iterator_to_array($this->stream->yield($start, period: 10)));
        assert(!empty($items));

        $last = array_last($items);
        $heartRateAvg = $last['heart_rate_avg'];

        self::assertGreaterThan(60.0, $heartRateAvg->value);
        self::assertLessThan(180.0, $heartRateAvg->value);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $last['timestamp']
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

        self::assertCount(7887, $series);

        foreach ($series as $data) {
            $timestamp = $data['timestamp'];
            self::assertEquals(
                0,
                date_diff($start, $timestamp)->days
            );
            self::assertGreaterThanOrEqual($start, $timestamp);

            $distance = $data['distance'];
            self::assertGreaterThanOrEqual(0.0, $distance);

            $heartRate = $data['heart_rate'];
            self::assertGreaterThan(60.0, $heartRate->value);
            self::assertLessThan(180.0, $heartRate->value);

            $heartRateAvg = $data['heart_rate_avg'];
            self::assertGreaterThanOrEqual(0.0, $heartRateAvg->value);
            self::assertLessThan(180.0, $heartRateAvg->value);

            $velocity = $data['velocity'];
            self::assertGreaterThanOrEqual(0.0, $velocity);
            self::assertLessThan(50.0, $velocity);

            $velocityAvg = $data['velocity_avg'];
            self::assertGreaterThanOrEqual(0.0, $velocityAvg);
            self::assertLessThan(50.0, $velocityAvg);
        }
    }
}
