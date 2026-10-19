<?php

namespace App\Tests\Unit;

use App\Strava\ActivityData;
use App\Twig\FormatMovingTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActivityDataTest extends TestCase
{
    #[Test]
    public function format_moving_time_should_work(): void
    {
        $data = ActivityDataFactory::createActivityData(14417573, 19881882510);

        self::assertInstanceOf(ActivityData::class, $data);

        self::assertEquals('12 min 0 s', FormatMovingTime::format($data->getMetaData()->getMovingTime()));
    }

    #[Test]
    public function should_contain_watts(): void
    {
        $data = ActivityDataFactory::createActivityData(14417573, 19881882510);

        self::assertInstanceOf(ActivityData::class, $data);

        $wattData = $data->getWattPoints();

        self::assertNotEmpty($data->getWattPoints());

        $nonZeros = array_filter(
            $wattData,
            static fn (array $dataPoint) => $dataPoint['value'] > 0
        );

        self::assertNotEmpty($nonZeros, 'There should be power data!');
    }
}
