<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\ActivityDataPoint;
use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\PaceZone;
use App\Momentum\Power;
use App\Momentum\Velocity;
use App\Momentum\ZoneSessions;
use App\Momentum\ZoneStatistics;
use PHPUnit\Framework\TestCase;

/**
 * Tests zone percentage aggregation to identify and debug the 149% issue.
 * Activity 18768361285 shows pace zones aggregating to 149% instead of 100%.
 * This suggests overlapping or duplicated zone sessions in the gap-merging logic.
 */
final class ZonePercentageAggregationTest extends TestCase
{
    use AthleteFactory;

    public function test_zone_percentage_aggregation_for_activity_18768361285(): void
    {
        // Load real activity data with 149% aggregation issue
        $jsonFile = __DIR__.'/../data/activity_stream_14417573_18768361285.json';
        self::assertFileExists($jsonFile, 'Activity stream file not found');

        $fileContents = file_get_contents($jsonFile);
        self::assertNotFalse($fileContents, 'Failed to read activity stream file');

        /** @var array<string, array<string, mixed>> $json */
        $json = json_decode($fileContents, true, flags: JSON_THROW_ON_ERROR);

        /** @var array<int, int> $timeData */
        $timeData = $json['time']['data'] ?? [];
        /** @var array<int, float> $velocityData */
        $velocityData = $json['velocity']['data'] ?? [];
        /** @var array<int, int|null> $hrData */
        $hrData = $json['heartrate']['data'] ?? [];

        self::assertNotEmpty($timeData, 'No time data found');
        self::assertNotEmpty($velocityData, 'No velocity data found');
        self::assertNotEmpty($hrData, 'No heart rate data found');

        // User's athlete configuration
        $treshold = new Pace(4.45, PaceUnit::MINUTES_PER_KILOMETER); // 4:27 min/km
        $maxHeartRate = new HeartRate(180);

        // Process zones
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        $dataPoints = [];
        for ($i = 0; $i < count($timeData); ++$i) {
            $timestamp = $startTime->add(new \DateInterval('PT'.((int) $timeData[$i]).'S'));
            $velocity = new Velocity((float) $velocityData[$i]);
            $hr = null !== $hrData[$i] ? (int) $hrData[$i] : 0;

            $dataPoints[] = new ActivityDataPoint([
                'timestamp' => $timestamp,
                'distance' => null,
                'heart_rate' => new HeartRate($hr),
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => null,
                'watts' => null,
                'watts_avg' => null,
            ]);
        }

        $athlete = self::createAthlete($treshold, Power::Zero(), $maxHeartRate);

        $endTime = $startTime->add(new \DateInterval('PT'.((int) end($timeData)).'S'));
        $zoneSessionsData = ZoneSessions::create($athlete, $endTime, $dataPoints);

        // Total time span of zone sessions (from first to last data point)
        $totalZoneSeconds = (int) end($timeData);  // 4781 seconds

        // Strava moving_time (actual time when moving, excluding pauses)
        $stravaMovingTime = 3196; // As reported by user

        // Calculate moving time (similar to Activity::getMovingTimeSeconds)
        $paceSessions = $zoneSessionsData->getPaceZoneSessions();
        $hrSessions = $zoneSessionsData->getHeartRateSessions();

        // When using total zone seconds (correct for unit test):
        $paceStatistics = ZoneStatistics::fromSessions($athlete, $paceSessions, PaceZone::cases());
        $paceStatsArrayCorrect = $paceStatistics->toArray($totalZoneSeconds);
        $paceTotalPercentageCorrect = array_sum(array_column($paceStatsArrayCorrect, 'duration_percentage'));

        // When using Strava moving_time (reproduces the 149% bug):
        $paceStatsArrayWithBug = $paceStatistics->toArray($stravaMovingTime);
        $paceTotalPercentageWithBug = array_sum(array_column($paceStatsArrayWithBug, 'duration_percentage'));

        $hrStatistics = ZoneStatistics::fromSessions($athlete, $hrSessions, HeartRateZone::cases());
        $hrStatsArrayCorrect = $hrStatistics->toArray($totalZoneSeconds);
        $hrTotalPercentageCorrect = array_sum(array_column($hrStatsArrayCorrect, 'duration_percentage'));

        // Debug output
        // echo sprintf("\n=== Zone Percentage Aggregation - 149%% Bug Reproduction ===\n");
        // echo sprintf("Activity: 18768361285\n");
        // echo sprintf("Zone sessions total: %d seconds\n", $totalZoneSeconds);
        // echo sprintf("Strava moving_time: %d seconds (from Activity entity)\n\n", $stravaMovingTime);
        // echo sprintf("CORRECT - Using zone session total (%d secs as denominator):\n", $totalZoneSeconds);
        // foreach ($paceStatsArrayCorrect as $stat) {
        //     echo sprintf("  %s: %.2f%% (%d secs)\n", $stat['zone'], $stat['duration_percentage'], $stat['duration_seconds']);
        // }
        // echo sprintf("  TOTAL: %.2f%% ✓\n\n", $paceTotalPercentageCorrect);
        // echo sprintf("BUG - Using Strava moving_time (%d secs as denominator):\n", $stravaMovingTime);
        // foreach ($paceStatsArrayWithBug as $stat) {
        //     echo sprintf("  %s: %.2f%% (%d secs)\n", $stat['zone'], $stat['duration_percentage'], $stat['duration_seconds']);
        // }
        // echo sprintf("  TOTAL: %.2f%% ❌ (Should be 100%%)\n\n", $paceTotalPercentageWithBug);
        // echo sprintf("Heart Rate Zones (using zone session total):\n");
        // foreach ($hrStatsArrayCorrect as $stat) {
        //     echo sprintf("  %s: %.2f%%\n", $stat['zone'], $stat['duration_percentage']);
        // }
        // echo sprintf("  TOTAL: %.2f%% ✓\n\n", $hrTotalPercentageCorrect);

        // Assertions for correct calculation
        self::assertEqualsWithDelta(100.0, $paceTotalPercentageCorrect, 0.5, 'Pace zones should aggregate to 100%');
        self::assertEqualsWithDelta(100.0, $hrTotalPercentageCorrect, 0.5, 'HR zones should aggregate to 100%');

        // Document the bug: when using moving_time, percentages exceed 100%
        self::assertGreaterThan(140, $paceTotalPercentageWithBug, sprintf(
            'Bug: Using moving_time causes %.1f%% (expected ~149%%)',
            $paceTotalPercentageWithBug
        ));
    }
}
