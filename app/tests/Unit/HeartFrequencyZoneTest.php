<?php

namespace App\Tests\Unit;

use App\Momentum\HeartFrequencyZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HeartFrequencyZoneTest extends TestCase
{
    #[Test]
    public function zone_1_warm_up_light(): void
    {
        // Zone 1: 50–60% of Max HR
        // With max HR of 200: 100-120 bpm
        $zone = HeartFrequencyZone::calculate(100, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_1, $zone);
        self::assertEquals('Warm-up/Light', $zone->value);
    }

    #[Test]
    public function zone_1_at_lower_boundary(): void
    {
        // Zone 1 at 50% (100 bpm)
        $zone = HeartFrequencyZone::calculate(100, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_1, $zone);
    }

    #[Test]
    public function zone_2_easy_recovery(): void
    {
        // Zone 2: 60–70% of Max HR
        // With max HR of 200: 120-140 bpm
        $zone = HeartFrequencyZone::calculate(130, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_2, $zone);
        self::assertEquals('Easy/Recovery', $zone->value);
    }

    #[Test]
    public function zone_2_at_lower_boundary(): void
    {
        // Zone 2 at 60% (120 bpm)
        $zone = HeartFrequencyZone::calculate(120, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_2, $zone);
    }

    #[Test]
    public function zone_3_aerobic_moderate(): void
    {
        // Zone 3: 70–80% of Max HR
        // With max HR of 200: 140-160 bpm
        $zone = HeartFrequencyZone::calculate(150, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_3, $zone);
        self::assertEquals('Aerobic/Moderate', $zone->value);
    }

    #[Test]
    public function zone_3_at_lower_boundary(): void
    {
        // Zone 3 at 70% (140 bpm)
        $zone = HeartFrequencyZone::calculate(140, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_3, $zone);
    }

    #[Test]
    public function zone_4_threshold_hard(): void
    {
        // Zone 4: 80–90% of Max HR
        // With max HR of 200: 160-180 bpm
        $zone = HeartFrequencyZone::calculate(170, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_4, $zone);
        self::assertEquals('Threshold/Hard', $zone->value);
    }

    #[Test]
    public function zone_4_at_lower_boundary(): void
    {
        // Zone 4 at 80% (160 bpm)
        $zone = HeartFrequencyZone::calculate(160, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_4, $zone);
    }

    #[Test]
    public function zone_5_maximum_sprinting(): void
    {
        // Zone 5: 90–100% of Max HR
        // With max HR of 200: 180-200 bpm
        $zone = HeartFrequencyZone::calculate(190, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_5, $zone);
        self::assertEquals('Maximum/Sprinting', $zone->value);
    }

    #[Test]
    public function zone_5_at_lower_boundary(): void
    {
        // Zone 5 at 90% (180 bpm)
        $zone = HeartFrequencyZone::calculate(180, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_5, $zone);
    }

    #[Test]
    public function zone_5_at_max_heart_rate(): void
    {
        // Zone 5 at 100% (200 bpm)
        $zone = HeartFrequencyZone::calculate(200, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_5, $zone);
    }

    #[Test]
    public function below_zone_1_minimum(): void
    {
        // Below 50% (less than 100 bpm)
        $zone = HeartFrequencyZone::calculate(99, 200);
        self::assertEquals(HeartFrequencyZone::ZONE_1, $zone);
    }

    #[Test]
    public function different_max_heart_rate(): void
    {
        // Zone 1: 50–60% of Max HR
        // With max HR of 180: 90-108 bpm
        self::assertEquals(HeartFrequencyZone::ZONE_1, HeartFrequencyZone::calculate(90, 180));
        self::assertEquals(HeartFrequencyZone::ZONE_1, HeartFrequencyZone::calculate(107, 180));

        // Zone 3: 70–80% of Max HR
        // With max HR of 180: 126-144 bpm
        self::assertEquals(HeartFrequencyZone::ZONE_3, HeartFrequencyZone::calculate(126, 180));
        self::assertEquals(HeartFrequencyZone::ZONE_3, HeartFrequencyZone::calculate(143, 180));

        // Zone 5: 90–100% of Max HR
        // With max HR of 180: 162-180 bpm
        self::assertEquals(HeartFrequencyZone::ZONE_5, HeartFrequencyZone::calculate(162, 180));
        self::assertEquals(HeartFrequencyZone::ZONE_5, HeartFrequencyZone::calculate(180, 180));
    }

    #[Test]
    public function zone_transitions(): void
    {
        // Test zone transitions with max HR of 200
        // Zone 1/2 boundary at 60% (120 bpm)
        self::assertEquals(HeartFrequencyZone::ZONE_1, HeartFrequencyZone::calculate(119, 200));
        self::assertEquals(HeartFrequencyZone::ZONE_2, HeartFrequencyZone::calculate(120, 200));

        // Zone 2/3 boundary at 70% (140 bpm)
        self::assertEquals(HeartFrequencyZone::ZONE_2, HeartFrequencyZone::calculate(139, 200));
        self::assertEquals(HeartFrequencyZone::ZONE_3, HeartFrequencyZone::calculate(140, 200));

        // Zone 3/4 boundary at 80% (160 bpm)
        self::assertEquals(HeartFrequencyZone::ZONE_3, HeartFrequencyZone::calculate(159, 200));
        self::assertEquals(HeartFrequencyZone::ZONE_4, HeartFrequencyZone::calculate(160, 200));

        // Zone 4/5 boundary at 90% (180 bpm)
        self::assertEquals(HeartFrequencyZone::ZONE_4, HeartFrequencyZone::calculate(179, 200));
        self::assertEquals(HeartFrequencyZone::ZONE_5, HeartFrequencyZone::calculate(180, 200));
    }
}
