<?php

namespace App\Tests\Unit;

use App\RunForge\MovingAverage;
use App\Strava\API\ActivityStream;
use PHPUnit\Framework\TestCase;

class MovingAverageTest extends TestCase
{
    public function test_decorate_with_moving_average(): void
    {
        // Deserialize ActivityStream from file
        $filePath = __DIR__.'/../data/activity_stream_14417573_18155703383.json';
        $activityStream = ActivityStream::fromJsonFile($filePath);

        // Verify the stream has velocity data
        self::assertTrue($activityStream->hasVelocityData());
        self::assertNotNull($activityStream->velocity);
        self::assertNotNull($activityStream->time);
        self::assertEquals(7887, $activityStream->velocity->getCount());

        // Decorate the ActivityStream with a MovingAverage over the velocity series
        $period = 5;
        $movingAverage = MovingAverage::ema($period);

        // Process velocity data
        for ($i = 0; $i < $activityStream->velocity->getCount(); ++$i) {
            $velocity = (float) $activityStream->velocity->getDataPoint($i);
            $timeOffset = (int) $activityStream->time->getDataPoint($i);
            $movingAverage->next($velocity, $timeOffset);
        }

        // Assert the moving average is properly initialized
        self::assertEquals($period, $movingAverage->period);
        self::assertEquals('EMA-5', $movingAverage->getName());

        // Assert that we have accumulated values
        self::assertNotEmpty($movingAverage->values);
        self::assertEquals($period, count($movingAverage->values));

        // Assert the final moving average value is reasonable and within expected range for velocity data
        $finalValue = $movingAverage->getValue();
        self::assertGreaterThan(1, $finalValue);
        self::assertLessThan(15, $finalValue);
    }
}
