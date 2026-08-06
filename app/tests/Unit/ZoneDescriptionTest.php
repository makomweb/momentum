<?php

namespace App\Tests\Unit;

use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceZone;
use App\Momentum\ZoneDescription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ZoneDescriptionTest extends TestCase
{
    use AthleteFactory;

    #[Test]
    public function heart_rate_zone_description(): void
    {
        $athlete = self::createAthlete(Pace::fromSecondsPerKilometer(240), new HeartRate(180));

        $description = new ZoneDescription($athlete);

        self::assertEquals('Warm-up/Light (< 108 bpm)', $description->getDescription(HeartRateZone::ZONE_1));
        self::assertEquals('Easy/Recovery (≥ 108 bpm)', $description->getDescription(HeartRateZone::ZONE_2));
        self::assertEquals('Aerobic/Moderate (≥ 125 bpm)', $description->getDescription(HeartRateZone::ZONE_3));
        self::assertEquals('Threshold/Hard (≥ 144 bpm)', $description->getDescription(HeartRateZone::ZONE_4));
        self::assertEquals('Maximum/Sprinting (≥ 162 bpm)', $description->getDescription(HeartRateZone::ZONE_5));
    }

    #[Test]
    public function pace_zone_description(): void
    {
        $athlete = self::createAthlete(Pace::fromSecondsPerKilometer(240), new HeartRate(180));

        $description = new ZoneDescription($athlete);

        self::assertEquals('Recovery (≤ 5:09 min/km)', $description->getDescription(PaceZone::ZONE_1));
        self::assertEquals('Basis (≤ 4:33 min/km)', $description->getDescription(PaceZone::ZONE_2));
        self::assertEquals('Tempo (≤ 4:14 min/km)', $description->getDescription(PaceZone::ZONE_3));
        self::assertEquals('Threshold (≤ 3:57 min/km)', $description->getDescription(PaceZone::ZONE_4));
        self::assertEquals('vO2max (> 3:57 min/km)', $description->getDescription(PaceZone::ZONE_5));
    }
}
