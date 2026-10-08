<?php

namespace App\Tests\Unit;

use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\PaceZone;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PaceZoneTest extends TestCase
{
    #[Test]
    public function zone_1_recovery_from_threshold(): void
    {
        // Zone 1 (Recovery): > 129% of treshold
        // With $treshold of 5:00 min/km: pace > 6:25 min/km
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);
        $zone = PaceZone::calculate(new Pace(6.5, PaceUnit::MINUTES_PER_KILOMETER), $treshold);
        self::assertEquals(PaceZone::ZONE_1, $zone);
        self::assertEquals('Recovery', $zone->value);
    }

    #[Test]
    public function zone_2_aerobic_base_from_threshold(): void
    {
        // Zone 2 (Aerobic Base): 114–129% of $treshold
        // With $treshold of 5:00 min/km: pace 5:42–6:25 min/km
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);
        $zone = PaceZone::calculate(new Pace(5.7, PaceUnit::MINUTES_PER_KILOMETER), $treshold);
        self::assertEquals(PaceZone::ZONE_2, $zone);
        self::assertEquals('Basis', $zone->value);
    }

    #[Test]
    public function zone_3_tempo_from_threshold(): void
    {
        // Zone 3 (Tempo): 106–113% of $treshold
        // With $treshold of 5:00 min/km: pace 5:18–5:38 min/km
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);
        $zone = PaceZone::calculate(new Pace(5.3, PaceUnit::MINUTES_PER_KILOMETER), $treshold);
        self::assertEquals(PaceZone::ZONE_3, $zone);
        self::assertEquals('Tempo', $zone->value);
    }

    #[Test]
    public function zone_4_threshold_from_threshold(): void
    {
        // Zone 4 (Threshold): 99–105% of $treshold
        // With $treshold of 5:00 min/km: pace 4:45–5:05 min/km
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);
        $zone = PaceZone::calculate(new Pace(5.02, PaceUnit::MINUTES_PER_KILOMETER), $treshold);
        self::assertEquals(PaceZone::ZONE_4, $zone);
        self::assertEquals('Threshold', $zone->value);
    }

    #[Test]
    public function zone_5_vo2_max_from_threshold(): void
    {
        // Zone 5 (VO2 Max): < 99% of $treshold
        // With $treshold of 5:00 min/km: pace < 4:57 min/km
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);
        $zone = PaceZone::calculate(new Pace(4.9, PaceUnit::MINUTES_PER_KILOMETER), $treshold);
        self::assertEquals(PaceZone::ZONE_5, $zone);
        self::assertEquals('vO2max', $zone->value);
    }

    #[Test]
    public function threshold_zone_boundaries(): void
    {
        // Test exact zone boundaries
        $treshold = new Pace(5.0, PaceUnit::MINUTES_PER_KILOMETER);

        // Zone 1/2 boundary at > 129% (>= 130%)
        // 130% of 5:00 = 6:30
        $pace130 = new Pace(6.5, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_1, PaceZone::calculate($pace130, $treshold));

        // Zone 2 at exactly 129% (< 130%)
        // 129% of 5:00 = 6:25 (6.45)
        $pace129 = new Pace(6.45, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_2, PaceZone::calculate($pace129, $treshold));

        // Zone 2/3 boundary at 114% (5:42 min/km)
        $pace114 = new Pace(5.7, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_2, PaceZone::calculate($pace114, $treshold));

        // Zone 3 at 113%
        // 113% of 5:00 = 5:38 (5.633)
        $pace113 = new Pace(5.633, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_3, PaceZone::calculate($pace113, $treshold));

        // Zone 3/4 boundary at 106% (5:18 min/km)
        $pace106 = new Pace(5.3, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_3, PaceZone::calculate($pace106, $treshold));

        // Zone 4 at 105%
        // 105% of 5:00 = 5:15 (5.25)
        $pace105 = new Pace(5.25, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_4, PaceZone::calculate($pace105, $treshold));

        // Zone 4/5 boundary at 99% (4:57 min/km)
        $pace99 = new Pace(4.95, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_4, PaceZone::calculate($pace99, $treshold));

        // Zone 5 at 98%
        // 98% of 5:00 = 4:54 (4.9)
        $pace98 = new Pace(4.9, PaceUnit::MINUTES_PER_KILOMETER);
        self::assertEquals(PaceZone::ZONE_5, PaceZone::calculate($pace98, $treshold));
    }

    #[Test]
    public function different_treshold_values(): void
    {
        // Test with different $treshold values

        // $treshold of 4:30 (4.5 min/km)
        $treshold1 = new Pace(4.5, PaceUnit::MINUTES_PER_KILOMETER);
        // Zone 1: > 129% = 4.5 * 1.30 = 5.85
        self::assertEquals(PaceZone::ZONE_1, PaceZone::calculate(new Pace(6.0, PaceUnit::MINUTES_PER_KILOMETER), $treshold1));
        // Zone 5: < 99% = 4.5 * 0.99 = 4.455
        self::assertEquals(PaceZone::ZONE_5, PaceZone::calculate(new Pace(4.4, PaceUnit::MINUTES_PER_KILOMETER), $treshold1));

        // $treshold of 6:00 (6.0 min/km)
        $treshold2 = new Pace(6.0, PaceUnit::MINUTES_PER_KILOMETER);
        // Zone 1: > 129% = 6.0 * 1.30 = 7.8
        self::assertEquals(PaceZone::ZONE_1, PaceZone::calculate(new Pace(7.9, PaceUnit::MINUTES_PER_KILOMETER), $treshold2));
        // Zone 5: < 99% = 6.0 * 0.99 = 5.94
        self::assertEquals(PaceZone::ZONE_5, PaceZone::calculate(new Pace(5.9, PaceUnit::MINUTES_PER_KILOMETER), $treshold2));
    }
}
