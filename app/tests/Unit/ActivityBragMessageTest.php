<?php

namespace App\Tests\Unit;

use App\Momentum\ActivityBragMessage;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActivityBragMessageTest extends TestCase
{
    private ActivityBragMessage $generator;

    protected function setUp(): void
    {
        $this->generator = new ActivityBragMessage();
    }

    #[Test]
    public function generates_quick_dash_for_very_short_runs(): void
    {
        $activity = [
            'distance' => 1.0,
            'moving_time' => 60,
            'average_speed' => 15.0,
            'max_speed' => 20.0,
            'type' => 'Run',
            'name' => 'Quick Jog',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Quick dash', $message);
        self::assertStringContainsString('Quick Jog', $message);
    }

    #[Test]
    public function generates_fast_pace_message_for_blazing_runs(): void
    {
        $activity = [
            'distance' => 5.0,
            'moving_time' => 1200, // 20 minutes, ~4:00 min/km pace
            'average_speed' => 4.17, // ~4:00 min/km
            'max_speed' => 5.0,
            'type' => 'Run',
            'name' => 'Speed Session',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Blazing fast', $message);
    }

    #[Test]
    public function generates_strong_effort_message_for_good_pace(): void
    {
        $activity = [
            'distance' => 5.0,
            'moving_time' => 1500, // 25 minutes, ~5:00 min/km pace
            'average_speed' => 3.33, // ~5:00 min/km
            'max_speed' => 4.0,
            'type' => 'Run',
            'name' => 'Morning Run',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Strong effort', $message);
    }

    #[Test]
    public function generates_solid_run_message_for_steady_pace(): void
    {
        $activity = [
            'distance' => 5.0,
            'moving_time' => 1800, // 30 minutes, ~6:00 min/km pace
            'average_speed' => 2.78, // ~6:00 min/km
            'max_speed' => 3.5,
            'type' => 'Run',
            'name' => 'Steady State',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Solid', $message);
    }

    #[Test]
    public function generates_long_run_message_for_10plus_km(): void
    {
        $activity = [
            'distance' => 12.0,
            'moving_time' => 3600, // 1 hour
            'average_speed' => 3.33, // ~6:00 min/km
            'max_speed' => 4.0,
            'type' => 'Run',
            'name' => 'Long Run',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Great distance', $message);
    }

    #[Test]
    public function generates_epic_message_for_distance_over_user_average(): void
    {
        $activity = [
            'distance' => 12.0,
            'moving_time' => 2400,
            'average_speed' => 5.0,
            'max_speed' => 6.0,
            'type' => 'Run',
            'name' => 'Extra Long',
        ];
        $userAverageDistance = 7.0;

        $message = $this->generator->generate($activity, $userAverageDistance);

        self::assertStringContainsString('That was epic', $message);
    }

    #[Test]
    public function generates_speedy_message_for_fast_bike_rides(): void
    {
        $activity = [
            'distance' => 30.0,
            'moving_time' => 3600,
            'average_speed' => 8.33, // ~30 km/h
            'max_speed' => 10.0,
            'type' => 'Ride',
            'name' => 'Speedy Ride',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('speedy', $message);
    }

    #[Test]
    public function generates_epic_message_for_long_bike_ride(): void
    {
        $activity = [
            'distance' => 60.0,
            'moving_time' => 7200,
            'average_speed' => 8.33,
            'max_speed' => 10.0,
            'type' => 'Ride',
            'name' => 'Epic Ride',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Epic distance', $message);
    }

    #[Test]
    public function generates_impressive_message_for_long_swim(): void
    {
        $activity = [
            'distance' => 2500.0, // 2.5km
            'moving_time' => 1500,
            'average_speed' => 1.67,
            'max_speed' => 2.0,
            'type' => 'Swim',
            'name' => 'Pool Session',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Impressive distance', $message);
    }

    #[Test]
    public function generates_strong_swim_message_for_shorter_swim(): void
    {
        $activity = [
            'distance' => 1000.0,
            'moving_time' => 600,
            'average_speed' => 1.67,
            'max_speed' => 2.0,
            'type' => 'Swim',
            'name' => 'Quick Swim',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Strong swim', $message);
    }

    #[Test]
    public function generates_generic_message_for_walk(): void
    {
        $activity = [
            'distance' => 3.0,
            'moving_time' => 1800,
            'average_speed' => 1.67,
            'max_speed' => 2.0,
            'type' => 'Walk',
            'name' => 'Evening Walk',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Nice', $message);
    }

    #[Test]
    public function includes_activity_name_in_message(): void
    {
        $activity = [
            'distance' => 5.0,
            'moving_time' => 1500,
            'average_speed' => 3.33,
            'max_speed' => 4.0,
            'type' => 'Run',
            'name' => 'My Custom Activity Name',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Strong effort', $message);
    }

    #[Test]
    public function handles_missing_activity_name(): void
    {
        $activity = [
            'distance' => 5.0,
            'moving_time' => 1500,
            'average_speed' => 3.33,
            'max_speed' => 4.0,
            'type' => 'Run',
        ];

        $message = $this->generator->generate($activity);

        self::assertStringContainsString('Strong effort', $message);
    }
}
