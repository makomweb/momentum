<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\ActivityDataPoint;
use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\PaceZone;
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
            ]);
        }

        $endTime = $startTime->add(new \DateInterval('PT'.((int) end($timeData)).'S'));

        // Total time span of zone sessions (from first to last data point)
        $totalZoneSeconds = (int) end($timeData);  // 4781 seconds

        // Strava moving_time (actual time when moving, excluding pauses)
        $stravaMovingTime = 3196; // As reported by user

        // Find moving time window for Option A fix
        $firstMoving = null;
        $lastMoving = null;
        for ($i = 0; $i < count($velocityData); ++$i) {
            if ($velocityData[$i] > 0) {
                if (null === $firstMoving) {
                    $firstMoving = $timeData[$i];
                }
                $lastMoving = $timeData[$i];
            }
        }

        // Assert we found a moving window
        self::assertNotNull($firstMoving, 'Should have found first moving point');
        self::assertNotNull($lastMoving, 'Should have found last moving point');
        /** @var array{0: int, 1: int} $movingTimeWindow */
        $movingTimeWindow = [$firstMoving, $lastMoving];

        // Test WITHOUT Option A fix (full data points)
        $zoneSessionsDataFull = ZoneSessions::create(
            self::createAthlete($treshold, $maxHeartRate),
            $endTime,
            $dataPoints
        );
        $paceSessionsFull = $zoneSessionsDataFull->getPaceZoneSessions();
        $hrSessionsFull = $zoneSessionsDataFull->getHeartRateSessions();

        // Test WITH Option A fix (filtered by moving time window)
        $zoneSessionsDataFiltered = ZoneSessions::create(
            self::createAthlete($treshold, $maxHeartRate),
            $endTime,
            $dataPoints,
            $movingTimeWindow
        );
        $paceSessionsFiltered = $zoneSessionsDataFiltered->getPaceZoneSessions();
        $hrSessionsFiltered = $zoneSessionsDataFiltered->getHeartRateSessions();

        // Calculate totals
        $totalPaceSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $paceSessionsFiltered));
        $totalHrSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $hrSessionsFiltered));

        // Create statistics
        $paceStatisticsFull = ZoneStatistics::fromSessions($paceSessionsFull, PaceZone::cases());
        $hrStatisticsFull = ZoneStatistics::fromSessions($hrSessionsFull, HeartRateZone::cases());

        $paceStatisticsFiltered = ZoneStatistics::fromSessions($paceSessionsFiltered, PaceZone::cases());
        $hrStatisticsFiltered = ZoneStatistics::fromSessions($hrSessionsFiltered, HeartRateZone::cases());

        // Test without fix using moving_time (reproduces bug)
        $paceStatsArrayBuggy = $paceStatisticsFull->toArray($stravaMovingTime);
        $paceTotalPercentageBuggy = array_sum(array_column($paceStatsArrayBuggy, 'duration_percentage'));

        // Test with fix - zones filtered to moving window
        $paceStatsArrayFixed = $paceStatisticsFiltered->toArray($totalPaceSeconds);
        $paceTotalPercentageFixed = array_sum(array_column($paceStatsArrayFixed, 'duration_percentage'));

        $hrStatsArrayFixed = $hrStatisticsFiltered->toArray($totalHrSeconds);
        $hrTotalPercentageFixed = array_sum(array_column($hrStatsArrayFixed, 'duration_percentage'));

        // Debug output
        echo sprintf("\n=== Zone Percentage Aggregation Fix - Option A ===\n");
        echo sprintf("Activity: 18768361285\n");
        echo sprintf("Total time span: %d seconds\n", $totalZoneSeconds);
        echo sprintf("Strava moving_time: %d seconds\n", $stravaMovingTime);
        echo sprintf("Filtered zone sessions total: %d seconds\n\n", $totalPaceSeconds);

        echo sprintf("WITHOUT Fix (using full data + moving_time divider):\n");
        foreach ($paceStatsArrayBuggy as $stat) {
            echo sprintf("  %s: %.2f%% (%d secs)\n", $stat['zone'], $stat['duration_percentage'], $stat['duration_seconds']);
        }
        echo sprintf("  TOTAL: %.2f%% ❌ (Bug: 149.59%%)\n\n", $paceTotalPercentageBuggy);

        echo sprintf("WITH Fix (Option A - filtered data):\n");
        foreach ($paceStatsArrayFixed as $stat) {
            echo sprintf("  %s: %.2f%% (%d secs)\n", $stat['zone'], $stat['duration_percentage'], $stat['duration_seconds']);
        }
        echo sprintf("  TOTAL: %.2f%% ✓ (Fixed: 100%%)\n\n", $paceTotalPercentageFixed);

        echo sprintf("Heart Rate Zones (with fix):\n");
        foreach ($hrStatsArrayFixed as $stat) {
            echo sprintf("  %s: %.2f%%\n", $stat['zone'], $stat['duration_percentage']);
        }
        echo sprintf("  TOTAL: %.2f%% ✓\n\n", $hrTotalPercentageFixed);

        // Assertions
        self::assertGreaterThan(140, $paceTotalPercentageBuggy, 'Without fix: should show 149%% bug');
        self::assertEqualsWithDelta(100.0, $paceTotalPercentageFixed, 0.5, 'With fix: pace zones should aggregate to 100%');
        self::assertEqualsWithDelta(100.0, $hrTotalPercentageFixed, 0.5, 'With fix: HR zones should aggregate to 100%');
    }
}
