<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceZone;
use App\Momentum\ZoneTransitionTracker;
use App\Strava\API\Velocity;
use PHPUnit\Framework\TestCase;

final class RealActivityZoneValidationTest extends TestCase
{
    public function test_real_activity_vo2max_zone_validation(): void
    {
        // Load real activity data
        $jsonFile = __DIR__.'/../../var/activity_stream_14417573_18355632062.json';
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
        $ftp = new Pace(6.0); // 6:00 min/km FTP
        $maxHeartRate = 190;

        // Process zones
        $tracker = new ZoneTransitionTracker();
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        for ($i = 0; $i < count($timeData); ++$i) {
            $timestamp = $startTime->add(new \DateInterval('PT'.((int) $timeData[$i]).'S'));
            $velocity = new Velocity((float) $velocityData[$i]);
            $hr = null !== $hrData[$i] ? (int) $hrData[$i] : null;

            $tracker->processPoint(
                timestamp: $timestamp,
                pace: $velocity->asPace(),
                ftp: $ftp,
                heartRate: $hr,
                maxHeartRate: $maxHeartRate,
            );
        }

        $endTime = $startTime->add(new \DateInterval('PT'.((int) $timeData[-1]).'S'));
        $tracker->finalize($endTime);

        $paceSessions = $tracker->getPaceZoneSessions();
        $totalSeconds = (int) $timeData[-1];

        // Analyze VO2max sessions
        $vo2maxTime = 0;
        $vo2maxWithHighHR = 0;
        $vo2maxSessions = [];

        foreach ($paceSessions as $session) {
            if (PaceZone::ZONE_5 === $session->zone) {
                $vo2maxTime += $session->getDurationSeconds();
                $vo2maxSessions[] = $session;

                // Check HR during this session
                $sessionStart = $session->startTime->getTimestamp();
                $sessionEnd = $session->endTime->getTimestamp();
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
                    //     $session->getDurationSeconds(),
                    //     $avgHRInSession,
                    //     $maxHRInSession
                    // );

                    // VO2max should have HR > 80% of max (>152 for 190 max)
                    if ($maxHRInSession >= 0.80 * $maxHeartRate) {
                        $vo2maxWithHighHR += $session->getDurationSeconds();
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

        $ftp = new Pace(6.0);
        $maxHeartRate = 190;

        $tracker = new ZoneTransitionTracker();
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Scenario: Quick velocity spike (>VO2max pace) but low HR (Zone 1)
        // This should NOT be classified as VO2max
        for ($i = 0; $i < 60; ++$i) {
            $timestamp = $startTime->add(new \DateInterval("PT{$i}S"));

            // High velocity: 5:00 pace = 3.33 m/s (100% of 6:00 FTP → Zone 5)
            $velocity = new Velocity(3.33);

            // But low HR: 95 bpm (50% of 190 = Zone 1 recovery)
            $hr = 95;

            $tracker->processPoint(
                timestamp: $timestamp,
                pace: $velocity->asPace(),
                ftp: $ftp,
                heartRate: $hr,
                maxHeartRate: $maxHeartRate,
            );
        }

        $tracker->finalize($startTime->add(new \DateInterval('PT60S')));

        $paceSessions = $tracker->getPaceZoneSessions();
        $hrSessions = $tracker->getHeartRateZoneSessions();

        // CURRENT BEHAVIOR (wrong):
        // This will be classified as ZONE_5 (VO2max) because only pace is considered
        self::assertCount(1, $paceSessions);
        self::assertTrue(PaceZone::ZONE_5 === $paceSessions[0]->zone, 'Currently classified as VO2max based on pace alone');

        // HR zone is actually Zone 1 (recovery)
        self::assertCount(1, $hrSessions);
        self::assertTrue(HeartRateZone::ZONE_1 === $hrSessions[0]->zone, 'HR zone is recovery');

        // echo "\n=== Zone Mismatch Example ===\n";
        // echo 'Pace zone: '.$paceSessions[0]->zone->value."\n";
        // echo 'HR zone: '.$hrSessions[0]->zone->value."\n";
        // echo "^ This is the problem - pace and HR zones don't match!\n";
    }
}
