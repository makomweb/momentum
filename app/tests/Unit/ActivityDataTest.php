<?php

namespace App\Tests\Unit;

use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceZone;
use App\Momentum\Power;
use App\Momentum\ZoneDescription;
use App\Strava\ActivityData;
use App\Twig\FormatMovingTime;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActivityDataTest extends TestCase
{
    #[Test]
    public function format_moving_time_should_work(): void
    {
        $data = ActivityDataFactory::createActivityData(14417573, 19881882510);

        self::assertInstanceOf(ActivityData::class, $data);

        self::assertEquals('12 min 0 s', FormatMovingTime::format($data->getMetaData()->getMovingTime()));
    }

    #[Test]
    public function should_contain_watts(): void
    {
        $data = ActivityDataFactory::createActivityData(14417573, 19881882510);

        self::assertInstanceOf(ActivityData::class, $data);

        $wattData = $data->getWattPoints();

        self::assertNotEmpty($data->getWattPoints());

        $nonZeros = array_filter(
            $wattData,
            static fn (array $dataPoint) => $dataPoint['value'] > 0
        );

        self::assertNotEmpty($nonZeros, 'There should be power data!');
    }

    #[Test]
    public function pace_zone_descriptions_should_be_correct_and_not_overwritten(): void
    {
        $data = ActivityDataFactory::createActivityData(14417573, 19881882510);

        // Get the internal data without calling toArray() to avoid profile picture requirement
        // Just verify that we can get velocity data points without errors
        $dataPoints = $data->getDataPoints();
        self::assertNotEmpty($dataPoints, 'Activity should have data points');

        // Verify the fix by checking that pace and heart rate zone descriptions are different
        // even though some zones have the same name (Recovery, Threshold)
        $athlete = AthleteFactory::createAthlete(
            Pace::fromSecondsPerKilometer(240),
            Power::Zero(),
            new HeartRate(180)
        );
        $paceZone = PaceZone::ZONE_1;
        $hrZone = HeartRateZone::ZONE_2;

        // Both have the same zone name "Recovery"
        self::assertEquals('Recovery', $paceZone->value);
        self::assertEquals('Recovery', $hrZone->value);

        // But their descriptions should be different
        $zoneDescription = new ZoneDescription($athlete);
        $paceDesc = $zoneDescription->getDescription($paceZone);
        $hrDesc = $zoneDescription->getDescription($hrZone);

        // Pace should have min/km format (specific to pace)
        self::assertStringContainsString('min/km', $paceDesc, "Pace zone description should have min/km: $paceDesc");
        // HR should NOT have min/km format
        self::assertStringNotContainsString('min/km', $hrDesc, "HR zone description should NOT have min/km: $hrDesc");

        // Verify that they are NOT the same (this was the bug - they were getting mixed up)
        self::assertNotEquals($paceDesc, $hrDesc, 'Pace and HR descriptions should be different even though they have the same zone name');

        // Test with Threshold zones too (ZONE_4)
        $paceZone4 = PaceZone::ZONE_4;
        $hrZone4 = HeartRateZone::ZONE_4;

        self::assertEquals('Threshold', $paceZone4->value);
        self::assertEquals('Threshold', $hrZone4->value);

        $paceDesc4 = $zoneDescription->getDescription($paceZone4);
        $hrDesc4 = $zoneDescription->getDescription($hrZone4);

        self::assertStringContainsString('min/km', $paceDesc4);
        self::assertStringNotContainsString('min/km', $hrDesc4);
        self::assertNotEquals($paceDesc4, $hrDesc4);
    }
}
