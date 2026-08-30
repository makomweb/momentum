<?php

namespace App\Tests\Unit;

use App\Momentum\PaceUnit;
use App\Momentum\Speed;
use App\Momentum\SpeedUnit;
use App\Momentum\Velocity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class VelocityTest extends TestCase
{
    #[Test]
    public function convert_to_kmh(): void
    {
        $velocity = new Velocity(metersPerSecond: 10.0);

        $speed = $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR);

        self::assertEquals(SpeedUnit::KILOMETERS_PER_HOUR, $speed->unit);
        self::assertEquals(36.0, $speed->value);
    }

    #[Test]
    public function convert_to_pace(): void
    {
        $velocity = new Velocity(metersPerSecond: 4.0);

        $pace = $velocity->asPace();

        self::assertEquals(PaceUnit::PER_KILOMETER, $pace->unit);
        self::assertEqualsWithDelta(4.17, $pace->value, 0.01);
    }

    #[Test]
    public function convert_to_ms(): void
    {
        $speed = new Speed(100.0, SpeedUnit::KILOMETERS_PER_HOUR);

        $ms = $speed->as(SpeedUnit::METERS_PER_SECOND);

        self::assertEquals(SpeedUnit::METERS_PER_SECOND, $ms->unit);
        self::assertEqualsWithDelta(27.77, $ms->value, 0.01);
    }

    #[Test]
    public function format_pace(): void
    {
        $velocity = new Velocity(metersPerSecond: 4.0);

        $pace = $velocity->asPace();

        self::assertEquals('4:10 /km', (string) $pace);
    }
}
