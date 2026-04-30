<?php

namespace App\Tests\Unit;

use App\Momentum\Zone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HeartRateZoneTest extends TestCase
{
    #[Test]
    public function zone_1_warm_up_light(): void
    {
        // Zone 1: 50–60% of Max HR
        // With max HR of 200: 100-120 bpm
        $zone = Zone::fromHeartFrequency(100, 200);
        self::assertEquals(Zone::ZONE_1, $zone);
        self::assertEquals('Warm-up/Light', $zone->value);
    }

    #[Test]
    public function zone_1_at_lower_boundary(): void
    {
        // Zone 1 at 50% (100 bpm)
        $zone = Zone::fromHeartFrequency(100, 200);
        self::assertEquals(Zone::ZONE_1, $zone);
    }

    #[Test]
    public function zone_2_easy_recovery(): void
    {
        // Zone 2: 60–70% of Max HR
        // With max HR of 200: 120-140 bpm
        $zone = Zone::fromHeartFrequency(130, 200);
        self::assertEquals(Zone::ZONE_2, $zone);
        self::assertEquals('Easy/Recovery', $zone->value);
    }

    #[Test]
    public function zone_2_at_lower_boundary(): void
    {
        // Zone 2 at 60% (120 bpm)
        $zone = Zone::fromHeartFrequency(120, 200);
        self::assertEquals(Zone::ZONE_2, $zone);
    }

    #[Test]
    public function zone_3_aerobic_moderate(): void
    {
        // Zone 3: 70–80% of Max HR
        // With max HR of 200: 140-160 bpm
        $zone = Zone::fromHeartFrequency(150, 200);
        self::assertEquals(Zone::ZONE_3, $zone);
        self::assertEquals('Aerobic/Moderate', $zone->value);
    }

    #[Test]
    public function zone_3_at_lower_boundary(): void
    {
        // Zone 3 at 70% (140 bpm)
        $zone = Zone::fromHeartFrequency(140, 200);
        self::assertEquals(Zone::ZONE_3, $zone);
    }

    #[Test]
    public function zone_4_threshold_hard(): void
    {
        // Zone 4: 80–90% of Max HR
        // With max HR of 200: 160-180 bpm
        $zone = Zone::fromHeartFrequency(170, 200);
        self::assertEquals(Zone::ZONE_4, $zone);
        self::assertEquals('Threshold/Hard', $zone->value);
    }

    #[Test]
    public function zone_4_at_lower_boundary(): void
    {
        // Zone 4 at 80% (160 bpm)
        $zone = Zone::fromHeartFrequency(160, 200);
        self::assertEquals(Zone::ZONE_4, $zone);
    }

    #[Test]
    public function zone_5_maximum_sprinting(): void
    {
        // Zone 5: 90–100% of Max HR
        // With max HR of 200: 180-200 bpm
        $zone = Zone::fromHeartFrequency(190, 200);
        self::assertEquals(Zone::ZONE_5, $zone);
        self::assertEquals('Maximum/Sprinting', $zone->value);
    }

    #[Test]
    public function zone_5_at_lower_boundary(): void
    {
        // Zone 5 at 90% (180 bpm)
        $zone = Zone::fromHeartFrequency(180, 200);
        self::assertEquals(Zone::ZONE_5, $zone);
    }

    #[Test]
    public function zone_5_at_max_heart_rate(): void
    {
        // Zone 5 at 100% (200 bpm)
        $zone = Zone::fromHeartFrequency(200, 200);
        self::assertEquals(Zone::ZONE_5, $zone);
    }

    #[Test]
    public function below_zone_1_minimum(): void
    {
        // Below 50% (less than 100 bpm)
        $zone = Zone::fromHeartFrequency(99, 200);
        self::assertEquals(Zone::ZONE_1, $zone);
    }

    #[Test]
    public function different_max_heart_rate(): void
    {
        // Zone 1: 50–60% of Max HR
        // With max HR of 180: 90-108 bpm
        self::assertEquals(Zone::ZONE_1, Zone::fromHeartFrequency(90, 180));
        self::assertEquals(Zone::ZONE_1, Zone::fromHeartFrequency(107, 180));

        // Zone 3: 70–80% of Max HR
        // With max HR of 180: 126-144 bpm
        self::assertEquals(Zone::ZONE_3, Zone::fromHeartFrequency(126, 180));
        self::assertEquals(Zone::ZONE_3, Zone::fromHeartFrequency(143, 180));

        // Zone 5: 90–100% of Max HR
        // With max HR of 180: 162-180 bpm
        self::assertEquals(Zone::ZONE_5, Zone::fromHeartFrequency(162, 180));
        self::assertEquals(Zone::ZONE_5, Zone::fromHeartFrequency(180, 180));
    }

    #[Test]
    public function zone_transitions(): void
    {
        // Test zone transitions with max HR of 200
        // Zone 1/2 boundary at 60% (120 bpm)
        self::assertEquals(Zone::ZONE_1, Zone::fromHeartFrequency(119, 200));
        self::assertEquals(Zone::ZONE_2, Zone::fromHeartFrequency(120, 200));

        // Zone 2/3 boundary at 70% (140 bpm)
        self::assertEquals(Zone::ZONE_2, Zone::fromHeartFrequency(139, 200));
        self::assertEquals(Zone::ZONE_3, Zone::fromHeartFrequency(140, 200));

        // Zone 3/4 boundary at 80% (160 bpm)
        self::assertEquals(Zone::ZONE_3, Zone::fromHeartFrequency(159, 200));
        self::assertEquals(Zone::ZONE_4, Zone::fromHeartFrequency(160, 200));

        // Zone 4/5 boundary at 90% (180 bpm)
        self::assertEquals(Zone::ZONE_4, Zone::fromHeartFrequency(179, 200));
        self::assertEquals(Zone::ZONE_5, Zone::fromHeartFrequency(180, 200));
    }
}
