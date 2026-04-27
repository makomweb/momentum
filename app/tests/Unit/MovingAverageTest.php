<?php

namespace App\Tests\Unit;

use App\Momentum\MovingAverage;
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

        $ema = array_reduce(
            iterator_to_array($this->stream->yieldSeries($start, 'velocity')),
            /** @param array{value: float|int, time_stamp: \DateTimeImmutable} $data */
            static fn (MovingAverage $carry, array $data) => $carry->with($data['value'], $data['time_stamp']),
            MovingAverage::create('Speed', start: $start, period: 10)
        );

        self::assertEquals('Speed (EMA-10)', $ema->getName());
        $value = $ema->getValue();
        self::assertGreaterThan(1, $value);
        self::assertLessThan(15, $value);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $ema->getTimestamp()
            )->days
        );
    }

    #[Test]
    public function calculate_moving_average_heart_rate(): void
    {
        $start = new \DateTimeImmutable();

        $ema = array_reduce(
            iterator_to_array($this->stream->yieldSeries($start, 'heart_rate')),
            /** @param array{value: float|int, time_stamp: \DateTimeImmutable} $data */
            static fn (MovingAverage $carry, array $data) => $carry->with($data['value'], $data['time_stamp']),
            MovingAverage::create('HF', start: $start, period: 10)
        );

        self::assertEquals('HF (EMA-10)', $ema->getName());
        $value = $ema->getValue();
        self::assertGreaterThan(60.0, $value);
        self::assertLessThan(180.0, $value);
        self::assertEquals(
            0,
            date_diff(
                $start,
                $ema->getTimestamp()
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
            self::assertGreaterThan(60.0, $heartRate);
            self::assertLessThan(180.0, $heartRate);

            $heartRateAvg = $data['heart_rate_avg'];
            self::assertGreaterThan(60.0, $heartRateAvg);
            self::assertLessThan(180.0, $heartRateAvg);

            $velocity = $data['velocity'];
            self::assertGreaterThanOrEqual(0.0, $velocity);
            self::assertLessThan(50.0, $velocity);

            $velocityAvg = $data['velocity_avg'];
            self::assertGreaterThanOrEqual(0.0, $velocityAvg);
            self::assertLessThan(50.0, $velocityAvg);
        }
    }
}
