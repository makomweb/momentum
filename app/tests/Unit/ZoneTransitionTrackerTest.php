<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\ActivityDataPoint;
use App\Momentum\HeartRate;
use App\Momentum\HeartRateZone;
use App\Momentum\Pace;
use App\Momentum\PaceZone;
use App\Momentum\Velocity;
use App\Momentum\ZoneTracker;
use App\Momentum\ZoneTransitionTracker;
use PHPUnit\Framework\TestCase;

final class ZoneTransitionTrackerTest extends TestCase
{
    use AthleteFactory;

    private ZoneTransitionTracker $tracker;
    private Pace $treshold;
    private HeartRate $maxHeartRate;

    protected function setUp(): void
    {
        // 6:00 min/km = 360 seconds per km
        $this->treshold = new Pace(6.0);
        $this->maxHeartRate = new HeartRate(200);

        $this->tracker = ZoneTransitionTracker::for(
            self::createAthlete($this->treshold, $this->maxHeartRate)
        );
    }

    public function test_single_pace_zone_transition(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Start in Zone 2 (Basis: 114-129% of FTP)
        // 6:00 FTP = 6.0 min/km
        // 7:00 pace = 7.0 min/km = 116% of FTP → Zone 2
        // velocity = 16.667 / 7.0 = 2.381 m/s
        $baseVelocity = new Velocity(2.381);

        for ($i = 0; $i < 60; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => $baseVelocity,
                'velocity_avg' => $baseVelocity,
            ]));
        }

        // Transition to Zone 5 (VO2max: < 99%)
        // 5:30 pace = 5.5 min/km = 92% of FTP → Zone 5
        // velocity = 16.667 / 5.5 = 3.03 m/s
        $fasterVelocity = new Velocity(3.03);

        for ($i = 60; $i < 120; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => $fasterVelocity,
                'velocity_avg' => $fasterVelocity,
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT120S')));

        $sessions = $this->tracker->getPaceZoneSessions();

        self::assertCount(2, $sessions, 'Should have 2 pace zone sessions');
        self::assertTrue($sessions[0]->isEqual(PaceZone::ZONE_2));
        self::assertEquals(60, $sessions[0]->getSeconds());
        self::assertTrue($sessions[1]->isEqual(PaceZone::ZONE_5));
        self::assertEquals(60, $sessions[1]->getSeconds());
    }

    public function test_heart_rate_zone_transition(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Dummy velocity (not used for HR zone test)
        $velocity = new Velocity(2.5);

        // Zone 2 (Easy/Recovery: 60-70% HR)
        // 60% of 200 = 120 bpm, 70% of 200 = 140 bpm
        // Use 130 bpm (in zone)
        for ($i = 0; $i < 50; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => new HeartRate(130),
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
            ]));
        }

        // Transition to Zone 4 (Threshold/Hard: 80-90%)
        // 80% of 200 = 160 bpm, 90% of 200 = 180 bpm
        // Use 170 bpm (in zone)
        for ($i = 50; $i < 100; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => new HeartRate(170),
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT100S')));

        $sessions = $this->tracker->getHeartRateZoneSessions();

        self::assertCount(2, $sessions, 'Should have 2 heart rate zone sessions');
        self::assertTrue($sessions[0]->isEqual(HeartRateZone::ZONE_2));
        self::assertEquals(50, $sessions[0]->getSeconds());
        self::assertTrue($sessions[1]->isEqual(HeartRateZone::ZONE_4));
        self::assertEquals(50, $sessions[1]->getSeconds());
    }

    public function test_multiple_transitions(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Zone 2: 7:00 pace = 2.381 m/s (116% of 6:00 FTP)
        // Zone 3: 6:18 pace = 2.62 m/s (106% of 6:00 FTP)  -- Need >= 106%
        // Zone 5: 5:30 pace = 3.03 m/s (92% of 6:00 FTP)
        $zones = [
            ['velocity' => new Velocity(2.381), 'duration' => 30, 'expectedZone' => PaceZone::ZONE_2],
            ['velocity' => new Velocity(2.62), 'duration' => 40, 'expectedZone' => PaceZone::ZONE_3],
            ['velocity' => new Velocity(3.03), 'duration' => 20, 'expectedZone' => PaceZone::ZONE_5],
            ['velocity' => new Velocity(2.381), 'duration' => 35, 'expectedZone' => PaceZone::ZONE_2],
        ];

        $timestamp = 0;
        foreach ($zones as $zone) {
            for ($i = 0; $i < $zone['duration']; ++$i) {
                $this->tracker->processPoint(new ActivityDataPoint([
                    'timestamp' => $startTime->add(new \DateInterval("PT{$timestamp}S")),
                    'distance' => null,
                    'heart_rate' => null,
                    'heart_rate_avg' => null,
                    'velocity' => $zone['velocity'],
                    'velocity_avg' => $zone['velocity'],
                ]));
                ++$timestamp;
            }
        }

        $this->tracker->finalize($startTime->add(new \DateInterval("PT{$timestamp}S")));

        $sessions = $this->tracker->getPaceZoneSessions();

        self::assertCount(4, $sessions, 'Should have 4 pace zone sessions');
        self::assertTrue($sessions[0]->isEqual(PaceZone::ZONE_2));
        self::assertEquals(30, $sessions[0]->getSeconds());
        self::assertTrue($sessions[1]->isEqual(PaceZone::ZONE_3));
        self::assertEquals(40, $sessions[1]->getSeconds());
        self::assertTrue($sessions[2]->isEqual(PaceZone::ZONE_5));
        self::assertEquals(20, $sessions[2]->getSeconds());
        self::assertTrue($sessions[3]->isEqual(PaceZone::ZONE_2));
        self::assertEquals(35, $sessions[3]->getSeconds());
    }

    public function test_zone_session_duration_percentage(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');
        $velocity = new Velocity(2.381); // Zone 2 pace

        for ($i = 0; $i < 100; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT100S')));

        $sessions = $this->tracker->getPaceZoneSessions();
        self::assertCount(1, $sessions);

        $percentage = $sessions[0]->getPercentage(100);
        self::assertEqualsWithDelta(100.0, $percentage, 0.01, 'Should be 100% of total time');
    }

    public function test_empty_activity(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');
        $this->tracker->finalize($startTime);

        self::assertCount(0, $this->tracker->getPaceZoneSessions());
        self::assertCount(0, $this->tracker->getHeartRateZoneSessions());
    }

    public function test_mixed_pace_and_heart_rate_zones(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Slow pace (Zone 2) with high HR (Zone 4)
        for ($i = 0; $i < 30; ++$i) {
            $velocity = Velocity::create(new Pace(7.0));
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => new HeartRate(170),
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT30S')));

        $paceSessions = $this->tracker->getPaceZoneSessions();
        $hrSessions = $this->tracker->getHeartRateZoneSessions();

        self::assertCount(1, $paceSessions);
        self::assertCount(1, $hrSessions);
        self::assertTrue($paceSessions[0]->isEqual(PaceZone::ZONE_2));
        self::assertTrue($hrSessions[0]->isEqual(HeartRateZone::ZONE_4));
    }

    public function test_no_heart_rate_data(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');
        $velocity = new Velocity(2.381); // Zone 2 pace

        for ($i = 0; $i < 50; ++$i) {
            // Don't provide heart rate data
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => $velocity,
                'velocity_avg' => $velocity,
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT50S')));

        self::assertCount(1, $this->tracker->getPaceZoneSessions());
        self::assertCount(0, $this->tracker->getHeartRateZoneSessions());
    }

    public function test_zone_session_timestamps(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        for ($i = 0; $i < 50; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => new Velocity(2.381), // Zone 2 pace
                'velocity_avg' => new Velocity(2.381),
            ]));
        }

        for ($i = 50; $i < 100; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => new Velocity(3.03), // Zone 5 pace
                'velocity_avg' => new Velocity(3.03),
            ]));
        }

        $endTime = $startTime->add(new \DateInterval('PT100S'));
        $this->tracker->finalize($endTime);

        $sessions = $this->tracker->getPaceZoneSessions();

        self::assertEquals($startTime, $sessions[0]->start);
        self::assertEquals($startTime->add(new \DateInterval('PT50S')), $sessions[0]->end);
        self::assertEquals($startTime->add(new \DateInterval('PT50S')), $sessions[1]->start);
        self::assertEquals($endTime, $sessions[1]->end);
    }

    public function test_gap_in_data_prevents_zone_extension(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        // Zone 2 pace for first 8 seconds (0-7)
        for ($i = 0; $i <= 7; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => new Velocity(2.381), // Zone 2 pace
                'velocity_avg' => new Velocity(2.381),
            ]));
        }

        self::assertEquals(5, ZoneTracker::GAP_THRESHOLD);

        // Gap: no points from seconds 8-14 (> 5 second gap threshold)
        // Resume with Zone 2 pace at second 15
        for ($i = 15; $i <= 30; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => null,
                'heart_rate_avg' => null,
                'velocity' => new Velocity(2.381), // Zone 2 pace (same zone!)
                'velocity_avg' => new Velocity(2.381),
            ]));
        }

        $endTime = $startTime->add(new \DateInterval('PT31S'));
        $this->tracker->finalize($endTime);

        $sessions = $this->tracker->getPaceZoneSessions();

        // Should have 2 sessions due to gap, not 1 extended session
        self::assertCount(2, $sessions);
        self::assertEquals(7, $sessions[0]->getSeconds());
        self::assertEquals(16, $sessions[1]->getSeconds());
    }

    public function test_finalize_does_not_create_negative_duration_when_end_time_precedes_last_point(): void
    {
        $startTime = new \DateTimeImmutable('2026-05-04 10:00:00');

        for ($i = 0; $i < 10; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => new HeartRate(150),
                'heart_rate_avg' => null,
                'velocity' => new Velocity(2.381),
                'velocity_avg' => new Velocity(2.381),
            ]));
        }

        for ($i = 20; $i < 30; ++$i) {
            $this->tracker->processPoint(new ActivityDataPoint([
                'timestamp' => $startTime->add(new \DateInterval("PT{$i}S")),
                'distance' => null,
                'heart_rate' => new HeartRate(150),
                'heart_rate_avg' => null,
                'velocity' => new Velocity(2.381),
                'velocity_avg' => new Velocity(2.381),
            ]));
        }

        $this->tracker->finalize($startTime->add(new \DateInterval('PT15S')));

        $paceSessions = $this->tracker->getPaceZoneSessions();
        $heartRateSessions = $this->tracker->getHeartRateZoneSessions();

        self::assertSame(2, count($paceSessions));
        self::assertSame(2, count($heartRateSessions));
        self::assertSame(9, $paceSessions[0]->getSeconds());
        self::assertSame(9, $paceSessions[1]->getSeconds());
        self::assertSame(9, $heartRateSessions[0]->getSeconds());
        self::assertSame(9, $heartRateSessions[1]->getSeconds());
    }
}
