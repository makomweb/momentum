<?php

namespace App\Tests\Unit;

use App\Strava\API\Velocity;
use App\Strava\API\VelocityUnit;
use PHPUnit\Framework\TestCase;

class VelocityTest extends TestCase
{
    public function test_convert_ms_to_kmh(): void
    {
        $ms = new Velocity(10.0, VelocityUnit::METER_PER_SECOND);

        $kmh = $ms->as(VelocityUnit::KILOMETER_PER_HOUR);

        self::assertEquals(VelocityUnit::KILOMETER_PER_HOUR, $kmh->unit);
        self::assertEquals(36.0, $kmh->value);
    }

    public function test_convert_ms_to_minkm(): void
    {
        $ms = new Velocity(4.0, VelocityUnit::METER_PER_SECOND);

        $minkm = $ms->as(VelocityUnit::MINUTES_PER_KILOMETER);

        self::assertEquals(VelocityUnit::MINUTES_PER_KILOMETER, $minkm->unit);
        self::assertEqualsWithDelta(4.17, $minkm->value, 0.01);
    }

    public function test_convert_kmh_ms(): void
    {
        $kmh = new Velocity(100.0, VelocityUnit::KILOMETER_PER_HOUR);

        $ms = $kmh->as(VelocityUnit::METER_PER_SECOND);

        self::assertEquals(VelocityUnit::METER_PER_SECOND, $ms->unit);
        self::assertEqualsWithDelta(27.77, $ms->value, 0.01);
    }
}
