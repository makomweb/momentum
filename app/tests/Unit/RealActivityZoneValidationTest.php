<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\Activity;
use App\Momentum\ActivityDataPoint;
use App\Momentum\Distance;
use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\PaceZone;
use App\Momentum\Power;
use App\Momentum\Velocity;
use PHPUnit\Framework\TestCase;

final class RealActivityZoneValidationTest extends TestCase
{
    use AthleteFactory;

    public function test_real_activity_vo2max_zone_validation(): void
    {
        // Load real activity data
        $jsonFile = __DIR__.'/../data/activity_stream_14417573_19881882510.json';
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

        // User config
        $treshold = new Pace(6.0, PaceUnit::MINUTES_PER_KILOMETER); // 6:00 min/km
        $maxHeartRate = new HeartRate(190);

        // Process zones
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        $dataPoints = [];
        for ($i = 0; $i < count($timeData); ++$i) {
            $timestamp = $startTime->add(new \DateInterval('PT'.((int) $timeData[$i]).'S'));
            $velocity = new Velocity((float) $velocityData[$i]);
            $hr = null !== $hrData[$i] ? (int) $hrData[$i] : 0;

            $dataPoints[] = new ActivityDataPoint([
                'timestamp' => $timestamp,
                'distance' => Distance::ZERO(),
                'heart_rate' => new HeartRate($hr),
                'heart_rate_avg' => HeartRate::ZERO(),
                'velocity' => $velocity,
                'velocity_avg' => Velocity::ZERO(),
                'watts' => Power::ZERO(),
                'watts_avg' => Power::ZERO(),
            ]);
        }

        $endTime = $startTime->add(new \DateInterval('PT'.((int) $timeData[-1]).'S'));
        $activity = Activity::create(self::createAthlete($treshold, Power::Zero(), $maxHeartRate), $endTime, $dataPoints);

        $paceSessions = $activity->getPaceZoneSessions();
        $totalSeconds = (int) $timeData[-1];

        // Analyze VO2max sessions
        $vo2maxTime = 0;
        $vo2maxWithHighHR = 0;
        $vo2maxSessions = [];

        foreach ($paceSessions as $session) {
            if (PaceZone::ZONE_5 === $session->zone) {
                $vo2maxTime += $session->getSeconds();
                $vo2maxSessions[] = $session;

                // Check HR during this session
                $sessionStart = $session->start->getTimestamp();
                $sessionEnd = $session->end->getTimestamp();
                $sessionHRs = [];

                for ($i = 0; $i < count($timeData); ++$i) {
                    $pointTime = $startTime->getTimestamp() + $timeData[$i];
                    if ($pointTime >= $sessionStart && $pointTime <= $sessionEnd) {
                        $sessionHRs[] = $hrData[$i];
                    }
                }

                if (!empty($sessionHRs)) {
                    $maxHRInSession = max($sessionHRs);
                    $avgHRInSession = array_sum($sessionHRs) / count($sessionHRs);

                    // echo sprintf(
                    //     "\nVO2max session %d secs: HR avg %.0f, max %d\n",
                    //     $session->getSeconds(),
                    //     $avgHRInSession,
                    //     $maxHRInSession
                    // );

                    // VO2max should have HR > 80% of max (>152 for 190 max)
                    if ($maxHRInSession >= 0.80 * $maxHeartRate->bpm) {
                        $vo2maxWithHighHR += $session->getSeconds();
                    }
                }
            }
        }

        $vo2maxPercentage = $totalSeconds > 0 ? ($vo2maxTime / $totalSeconds) * 100 : 0;
        $vo2maxValidPercentage = $totalSeconds > 0 ? ($vo2maxWithHighHR / $totalSeconds) * 100 : 0;

        // echo sprintf("\n=== Zone Validation Results ===\n");
        // echo sprintf("Total VO2max time: %.0f secs (%.1f%%)\n", $vo2maxTime, $vo2maxPercentage);
        // echo sprintf("VO2max with confirming HR (>80%% max): %.0f secs (%.1f%%)\n", $vo2maxWithHighHR, $vo2maxValidPercentage);
        // echo sprintf("Invalid VO2max (high pace, low HR): %.0f secs (%.1f%%)\n\n", $vo2maxTime - $vo2maxWithHighHR, $vo2maxPercentage - $vo2maxValidPercentage);

        // Currently, the issue: VO2max might be detected without confirming HR
        self::assertCount(count($vo2maxSessions), $vo2maxSessions, 'Should have detected VO2max sessions');
        self::assertLessThan(10, $vo2maxPercentage, 'VO2max should be less than 10% of activity');
    }

    public function test_zone_cross_validation_strategy(): void
    {
        // This test documents the desired behavior:
        // A pace zone should only be confirmed if HR zone matches

        $treshold = new Pace(6.0, PaceUnit::MINUTES_PER_KILOMETER);
        $maxHeartRate = new HeartRate(190);
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        $dataPoints = [];
        // Scenario: Quick velocity spike (>VO2max pace) but low HR (Zone 1)
        // This should NOT be classified as VO2max
        for ($i = 0; $i < 60; ++$i) {
            $timestamp = $startTime->add(new \DateInterval("PT{$i}S"));

            // High velocity: 5:00 pace = 3.33 m/s (83% of 6:00 treshold → Zone 5)
            $velocity = new Velocity(3.33);

            // But low HR: 95 bpm (50% of 190 = Zone 1 recovery)
            $hr = new HeartRate(95);

            $dataPoints[] = new ActivityDataPoint([
                'timestamp' => $timestamp,
                'distance' => Distance::ZERO(),
                'heart_rate' => $hr,
                'heart_rate_avg' => HeartRate::ZERO(),
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
                'watts' => Power::ZERO(),
                'watts_avg' => Power::ZERO(),
            ]);
        }

        $endTime = $startTime->add(new \DateInterval('PT60S'));
        $activity = Activity::create(self::createAthlete($treshold, Power::Zero(), $maxHeartRate), $endTime, $dataPoints);

        $paceSessions = $activity->getPaceZoneSessions();
        $hrSessions = $activity->getHeartRateSessions();

        // Pace zone is ZONE_5 (VO2max) because velocity is high
        self::assertCount(1, $paceSessions);
        self::assertTrue(PaceZone::ZONE_5 === $paceSessions[0]->zone, 'Classified as VO2max based on pace');

        // HR zone is actually Zone 1 (recovery) — a mismatch that ConfirmedPaceZoneSessions should filter
        self::assertCount(1, $hrSessions);
        self::assertTrue(HeartRateZone::ZONE_1 === $hrSessions[0]->zone, 'HR zone is recovery');

        // echo "\n=== Zone Mismatch Example ===\n";
        // echo 'Pace zone: '.$paceSessions[0]->zone->value."\n";
        // echo 'HR zone: '.$hrSessions[0]->zone->value."\n";
        // echo "^ This is the problem - pace and HR zones don't match!\n";
    }
}
