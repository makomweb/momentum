<?php

namespace App\Tests\Unit;

use App\Strava\API\Speed;
use App\Strava\API\SpeedUnit;
use PHPUnit\Framework\TestCase;

class SpeedTest extends TestCase
{
    public function test_convert_ms_to_kmh(): void
    {
        $ms = new Speed(10.0, SpeedUnit::METER_PER_SECOND);

        $kmh = $ms->as(SpeedUnit::KILOMETER_PER_HOUR);

        self::assertEquals(SpeedUnit::KILOMETER_PER_HOUR, $kmh->unit);
        self::assertEquals(36.0, $kmh->value);
    }

    public function test_convert_ms_to_minkm(): void
    {
        $ms = new Speed(4.0, SpeedUnit::METER_PER_SECOND);

        $minkm = $ms->as(SpeedUnit::MINUTES_PER_KILOMETER);

        self::assertEquals(SpeedUnit::MINUTES_PER_KILOMETER, $minkm->unit);
        self::assertEqualsWithDelta(4.17, $minkm->value, 0.01);
    }

    public function test_convert_kmh_ms(): void
    {
        $kmh = new Speed(100.0, SpeedUnit::KILOMETER_PER_HOUR);

        $ms = $kmh->as(SpeedUnit::METER_PER_SECOND);

        self::assertEquals(SpeedUnit::METER_PER_SECOND, $ms->unit);
        self::assertEqualsWithDelta(27.77, $ms->value, 0.01);
    }
}
