<?php

namespace App\Tests\Unit;

use App\RunForge\MovingAverage;
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
            /** @param array{value: float|int, time_stamp: DateTimeImmutable} $data */
            static fn (MovingAverage $carry, array $data) => $carry->withData($data),
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
            /** @param array{value: float|int, time_stamp: DateTimeImmutable} $data */
            static fn (MovingAverage $carry, array $data) => $carry->withData($data),
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
            $this->stream->yieldMovingAverages('heart_rate', $start, 10)
        );

        self::assertCount(7887, $series);

        $ema = array_last($series);
        assert($ema instanceof MovingAverage);

        self::assertEquals('heart_rate (EMA-10)', $ema->getName());
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
}
