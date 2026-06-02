<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\PaceZone;
use App\Momentum\TrainingMetrics;
use App\Momentum\ZoneSession;
use PHPUnit\Framework\TestCase;

class TrainingMetricsTest extends TestCase
{
    private \DateTimeImmutable $start;

    protected function setUp(): void
    {
        $this->start = new \DateTimeImmutable('2024-01-01 10:00:00');
    }

    private function session(PaceZone $zone, int $seconds): ZoneSession
    {
        return new ZoneSession(
            $zone,
            $this->start,
            $this->start->add(new \DateInterval("PT{$seconds}S"))
        );
    }

    public function test_easy_seconds_sum_zone_1_and_zone_2(): void
    {
        $sessions = [
            $this->session(PaceZone::ZONE_1, 600),
            $this->session(PaceZone::ZONE_2, 400),
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertSame(1000, $metrics->easySeconds);
    }

    public function test_hard_seconds_sum_zone_4_and_zone_5(): void
    {
        $sessions = [
            $this->session(PaceZone::ZONE_4, 300),
            $this->session(PaceZone::ZONE_5, 200),
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertSame(500, $metrics->hardSeconds);
    }

    public function test_medium_seconds_reflect_zone_3_time(): void
    {
        $sessions = [
            $this->session(PaceZone::ZONE_2, 2000), // easy
            $this->session(PaceZone::ZONE_3, 500),  // medium
            $this->session(PaceZone::ZONE_4, 800),  // hard
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertSame(500, $metrics->mediumSeconds);
    }

    public function test_medium_seconds_are_never_negative(): void
    {
        // Scenario: zone sessions (based on elapsed_time) sum to more than
        // totalSeconds (moving_time). Previously getMediumSeconds() was computed
        // as totalSeconds - easy - hard, which could go negative.
        $sessions = [
            $this->session(PaceZone::ZONE_2, 2500), // easy
            $this->session(PaceZone::ZONE_4, 1000), // hard
        ];

        // moving_time = 3000, but sessions sum to 3500 (elapsed_time based)
        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertGreaterThanOrEqual(0, $metrics->mediumSeconds);
    }

    public function test_percentage_relative_to_total(): void
    {
        $sessions = [
            new ZoneSession(
                PaceZone::ZONE_1,
                $this->start,
                $this->start->add(new \DateInterval('PT100S'))
            ),
            new ZoneSession(
                PaceZone::ZONE_3,
                $this->start->add(new \DateInterval('PT100S')),
                $this->start->add(new \DateInterval('PT200S'))
            ),
            new ZoneSession(
                PaceZone::ZONE_5,
                $this->start->add(new \DateInterval('PT200S')),
                $this->start->add(new \DateInterval('PT300S'))
            ),
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertEqualsWithDelta(33.0, $metrics->getEasyPercentage(), 0.4);
        self::assertEqualsWithDelta(33.0, $metrics->getMediumPercentage(), 0.4);
        self::assertEqualsWithDelta(33.0, $metrics->getHardPercentage(), 0.4);
    }

    public function test_zero_percentages_when_total_is_zero(): void
    {
        $metrics = TrainingMetrics::fromSessions([]);

        self::assertSame(0.0, $metrics->getEasyPercentage());
        self::assertSame(0.0, $metrics->getMediumPercentage());
        self::assertSame(0.0, $metrics->getHardPercentage());
    }

    public function test_is_balanced_with_even_distribution(): void
    {
        $sessions = [
            $this->session(PaceZone::ZONE_2, 1500), // 50% easy
            $this->session(PaceZone::ZONE_3, 300),  // 10% medium
            $this->session(PaceZone::ZONE_4, 1200), // 40% hard
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertTrue($metrics->isBalanced());
    }

    public function test_is_not_balanced_when_too_much_medium(): void
    {
        $sessions = [
            $this->session(PaceZone::ZONE_2, 1500), // 50% easy
            $this->session(PaceZone::ZONE_3, 1200), // 40% medium — too much
            $this->session(PaceZone::ZONE_4, 300),  // 10% hard
        ];

        $metrics = TrainingMetrics::fromSessions($sessions);

        self::assertFalse($metrics->isBalanced());
    }
}
