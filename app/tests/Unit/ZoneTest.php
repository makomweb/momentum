<?php

namespace App\Tests\Unit;

use App\Momentum\Pace;
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

    #[Test]
    public function zone_1_recovery_from_threshold(): void
    {
        // Zone 1 (Recovery): > 129% of FTP
        // With FTP of 5:00 min/km: pace > 6:25 min/km
        $ftp = new Pace(5.0);
        $zone = Zone::fromTreshold(new Pace(6.5), $ftp);
        self::assertEquals(Zone::ZONE_1, $zone);
        self::assertEquals('Warm-up/Light', $zone->value);
    }

    #[Test]
    public function zone_2_aerobic_base_from_threshold(): void
    {
        // Zone 2 (Aerobic Base): 114–129% of FTP
        // With FTP of 5:00 min/km: pace 5:42–6:25 min/km
        $ftp = new Pace(5.0);
        $zone = Zone::fromTreshold(new Pace(5.7), $ftp);
        self::assertEquals(Zone::ZONE_2, $zone);
        self::assertEquals('Easy/Recovery', $zone->value);
    }

    #[Test]
    public function zone_3_tempo_from_threshold(): void
    {
        // Zone 3 (Tempo): 106–113% of FTP
        // With FTP of 5:00 min/km: pace 5:18–5:38 min/km
        $ftp = new Pace(5.0);
        $zone = Zone::fromTreshold(new Pace(5.3), $ftp);
        self::assertEquals(Zone::ZONE_3, $zone);
        self::assertEquals('Aerobic/Moderate', $zone->value);
    }

    #[Test]
    public function zone_4_threshold_from_threshold(): void
    {
        // Zone 4 (Threshold): 99–105% of FTP
        // With FTP of 5:00 min/km: pace 4:45–5:05 min/km
        $ftp = new Pace(5.0);
        $zone = Zone::fromTreshold(new Pace(5.02), $ftp);
        self::assertEquals(Zone::ZONE_4, $zone);
        self::assertEquals('Threshold/Hard', $zone->value);
    }

    #[Test]
    public function zone_5_vo2_max_from_threshold(): void
    {
        // Zone 5 (VO2 Max): < 99% of FTP
        // With FTP of 5:00 min/km: pace < 4:57 min/km
        $ftp = new Pace(5.0);
        $zone = Zone::fromTreshold(new Pace(4.9), $ftp);
        self::assertEquals(Zone::ZONE_5, $zone);
        self::assertEquals('Maximum/Sprinting', $zone->value);
    }

    #[Test]
    public function threshold_zone_boundaries(): void
    {
        // Test exact zone boundaries
        $ftp = new Pace(5.0);

        // Zone 1/2 boundary at > 129% (>= 130%)
        // 130% of 5:00 = 6:30
        $pace130 = new Pace(6.5);
        self::assertEquals(Zone::ZONE_1, Zone::fromTreshold($pace130, $ftp));

        // Zone 2 at exactly 129% (< 130%)
        // 129% of 5:00 = 6:25 (6.45)
        $pace129 = new Pace(6.45);
        self::assertEquals(Zone::ZONE_2, Zone::fromTreshold($pace129, $ftp));

        // Zone 2/3 boundary at 114% (5:42 min/km)
        $pace114 = new Pace(5.7);
        self::assertEquals(Zone::ZONE_2, Zone::fromTreshold($pace114, $ftp));

        // Zone 3 at 113%
        // 113% of 5:00 = 5:38 (5.633)
        $pace113 = new Pace(5.633);
        self::assertEquals(Zone::ZONE_3, Zone::fromTreshold($pace113, $ftp));

        // Zone 3/4 boundary at 106% (5:18 min/km)
        $pace106 = new Pace(5.3);
        self::assertEquals(Zone::ZONE_3, Zone::fromTreshold($pace106, $ftp));

        // Zone 4 at 105%
        // 105% of 5:00 = 5:15 (5.25)
        $pace105 = new Pace(5.25);
        self::assertEquals(Zone::ZONE_4, Zone::fromTreshold($pace105, $ftp));

        // Zone 4/5 boundary at 99% (4:57 min/km)
        $pace99 = new Pace(4.95);
        self::assertEquals(Zone::ZONE_4, Zone::fromTreshold($pace99, $ftp));

        // Zone 5 at 98%
        // 98% of 5:00 = 4:54 (4.9)
        $pace98 = new Pace(4.9);
        self::assertEquals(Zone::ZONE_5, Zone::fromTreshold($pace98, $ftp));
    }

    #[Test]
    public function different_ftp_values(): void
    {
        // Test with different FTP values

        // FTP of 4:30 (4.5 min/km)
        $ftp1 = new Pace(4.5);
        // Zone 1: > 129% = 4.5 * 1.30 = 5.85
        self::assertEquals(Zone::ZONE_1, Zone::fromTreshold(new Pace(6.0), $ftp1));
        // Zone 5: < 99% = 4.5 * 0.99 = 4.455
        self::assertEquals(Zone::ZONE_5, Zone::fromTreshold(new Pace(4.4), $ftp1));

        // FTP of 6:00 (6.0 min/km)
        $ftp2 = new Pace(6.0);
        // Zone 1: > 129% = 6.0 * 1.30 = 7.8
        self::assertEquals(Zone::ZONE_1, Zone::fromTreshold(new Pace(7.9), $ftp2));
        // Zone 5: < 99% = 6.0 * 0.99 = 5.94
        self::assertEquals(Zone::ZONE_5, Zone::fromTreshold(new Pace(5.9), $ftp2));
    }
}
