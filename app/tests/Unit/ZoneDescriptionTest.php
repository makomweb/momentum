<?php

namespace App\Tests\Unit;

use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceZone;
use App\Momentum\Power;
use App\Momentum\ZoneDescription;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ZoneDescriptionTest extends TestCase
{
    #[Test]
    public function heart_rate_zone_description(): void
    {
        $athlete = AthleteFactory::createAthlete(Pace::fromSecondsPerKilometer(240), Power::Zero(), new HeartRate(180));

        $description = new ZoneDescription($athlete);

        self::assertEquals('activity.hr_zone_1 (< 108)', $description->getDescription(HeartRateZone::ZONE_1));
        self::assertEquals('activity.hr_zone_2 (≥ 108)', $description->getDescription(HeartRateZone::ZONE_2));
        self::assertEquals('activity.hr_zone_3 (≥ 125)', $description->getDescription(HeartRateZone::ZONE_3));
        self::assertEquals('activity.hr_zone_4 (≥ 144)', $description->getDescription(HeartRateZone::ZONE_4));
        self::assertEquals('activity.hr_zone_5 (≥ 162)', $description->getDescription(HeartRateZone::ZONE_5));
    }

    #[Test]
    public function pace_zone_description(): void
    {
        $athlete = AthleteFactory::createAthlete(Pace::fromSecondsPerKilometer(240), Power::Zero(), new HeartRate(180));

        $description = new ZoneDescription($athlete);

        self::assertEquals('activity.pace_zone_1 (≤ 5:09 min/km)', $description->getDescription(PaceZone::ZONE_1));
        self::assertEquals('activity.pace_zone_2 (≤ 4:33 min/km)', $description->getDescription(PaceZone::ZONE_2));
        self::assertEquals('activity.pace_zone_3 (≤ 4:14 min/km)', $description->getDescription(PaceZone::ZONE_3));
        self::assertEquals('activity.pace_zone_4 (≤ 3:57 min/km)', $description->getDescription(PaceZone::ZONE_4));
        self::assertEquals('activity.pace_zone_5 (> 3:57 min/km)', $description->getDescription(PaceZone::ZONE_5));
    }
}
