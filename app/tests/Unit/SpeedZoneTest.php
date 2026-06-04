<?php

namespace App\Tests\Unit;

use App\Momentum\SpeedZone;
use App\Momentum\Velocity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SpeedZoneTest extends TestCase
{
    #[Test]
    public function zone_1_very_slow_below_70_percent(): void
    {
        // Zone 1 (Very Slow): < 70% of average
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(6.0), $average);
        self::assertEquals(SpeedZone::ZONE_1, $zone);
        self::assertEquals('Very Slow', $zone->value);
    }

    #[Test]
    public function zone_2_moderate_70_90_percent(): void
    {
        // Zone 2 (Moderate): 70-90% of average
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(8.0), $average);
        self::assertEquals(SpeedZone::ZONE_2, $zone);
        self::assertEquals('Moderate', $zone->value);
    }

    #[Test]
    public function zone_3_comfortable_90_110_percent(): void
    {
        // Zone 3 (Comfortable): 90-110% of average
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(10.0), $average);
        self::assertEquals(SpeedZone::ZONE_3, $zone);
        self::assertEquals('Comfortable', $zone->value);
    }

    #[Test]
    public function zone_4_fast_110_130_percent(): void
    {
        // Zone 4 (Fast): 110-130% of average
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(12.0), $average);
        self::assertEquals(SpeedZone::ZONE_4, $zone);
        self::assertEquals('Fast', $zone->value);
    }

    #[Test]
    public function zone_5_sprint_above_130_percent(): void
    {
        // Zone 5 (Sprint): > 130% of average
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(13.5), $average);
        self::assertEquals(SpeedZone::ZONE_5, $zone);
        self::assertEquals('Sprint', $zone->value);
    }

    #[Test]
    public function zone_boundaries_exact_percentages(): void
    {
        $average = new Velocity(10.0);

        // Zone 1/2 boundary at 70%
        $zone69 = SpeedZone::calculate(new Velocity(6.9), $average);
        self::assertEquals(SpeedZone::ZONE_1, $zone69);

        $zone70 = SpeedZone::calculate(new Velocity(7.0), $average);
        self::assertEquals(SpeedZone::ZONE_2, $zone70);

        // Zone 2/3 boundary at 90%
        $zone89 = SpeedZone::calculate(new Velocity(8.9), $average);
        self::assertEquals(SpeedZone::ZONE_2, $zone89);

        $zone90 = SpeedZone::calculate(new Velocity(9.0), $average);
        self::assertEquals(SpeedZone::ZONE_3, $zone90);

        // Zone 3/4 boundary at 110%
        $zone109 = SpeedZone::calculate(new Velocity(10.9), $average);
        self::assertEquals(SpeedZone::ZONE_3, $zone109);

        $zone110 = SpeedZone::calculate(new Velocity(11.0), $average);
        self::assertEquals(SpeedZone::ZONE_4, $zone110);

        // Zone 4/5 boundary at 130% - must be > 130%, so at 131%
        $zone130 = SpeedZone::calculate(new Velocity(13.0), $average);
        self::assertEquals(SpeedZone::ZONE_4, $zone130);

        $zone131 = SpeedZone::calculate(new Velocity(13.1), $average);
        self::assertEquals(SpeedZone::ZONE_5, $zone131);
    }

    #[Test]
    public function zero_velocity_returns_comfortable_zone(): void
    {
        $average = new Velocity(10.0);
        $zone = SpeedZone::calculate(new Velocity(0.0), $average);
        self::assertEquals(SpeedZone::ZONE_3, $zone);
    }

    #[Test]
    public function zero_average_returns_comfortable_zone(): void
    {
        $current = new Velocity(10.0);
        $zone = SpeedZone::calculate($current, new Velocity(0.0));
        self::assertEquals(SpeedZone::ZONE_3, $zone);
    }

    #[Test]
    public function colors_are_defined(): void
    {
        foreach (SpeedZone::cases() as $zone) {
            $color = $zone->getColor();
            self::assertNotEmpty($color);
            self::assertStringStartsWith('#', $color);
        }
    }

    #[Test]
    public function different_average_speeds(): void
    {
        // Test with 20 km/h = 5.556 m/s average
        $avg20 = new Velocity(5.556);
        // 30 km/h = 8.333 m/s = 150% of 5.556 -> Zone 5
        $zone = SpeedZone::calculate(new Velocity(8.333), $avg20);
        self::assertEquals(SpeedZone::ZONE_5, $zone);

        // Test with 15 km/h = 4.167 m/s average
        $avg15 = new Velocity(4.167);
        // 10 km/h = 2.778 m/s = 66.7% of 4.167 -> Zone 1
        $zone = SpeedZone::calculate(new Velocity(2.778), $avg15);
        self::assertEquals(SpeedZone::ZONE_1, $zone);

        // 11 km/h = 3.056 m/s = 73.4% of 4.167 -> Zone 2
        $zone = SpeedZone::calculate(new Velocity(3.056), $avg15);
        self::assertEquals(SpeedZone::ZONE_2, $zone);
    }
}
