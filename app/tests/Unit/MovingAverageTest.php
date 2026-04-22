<?php

namespace App\Tests\Unit;

use App\RunForge\MovingAverage;
use App\Strava\API\ActivityStream;
use PHPUnit\Framework\TestCase;

class MovingAverageTest extends TestCase
{
    public function test_create_from_activity_stream(): void
    {
        $filePath = __DIR__.'/../data/activity_stream_14417573_18155703383.json';

        $stream = ActivityStream::fromJsonFile($filePath);

        self::assertTrue($stream->hasVelocityData());
        self::assertNotNull($stream->velocity);
        self::assertNotNull($stream->time);
        self::assertEquals(7887, $stream->velocity->getCount());

        $start = new \DateTimeImmutable();

        $ema = array_reduce(
            iterator_to_array($stream->yieldSeries($start, 'velocity')),
            /** @param array{value: float|int, time_stamp: DateTimeImmutable} $data */
            static fn (MovingAverage $carry, array $data) => $carry->withData($data),
            MovingAverage::create(start: $start, period: 5)
        );

        self::assertEquals('(E)MA-5', $ema->getName());
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
}
