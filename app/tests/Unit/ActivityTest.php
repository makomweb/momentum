<?php

namespace App\Tests\Unit;

use App\Strava\API\Activity;
use PHPUnit\Framework\TestCase;

class ActivityTest extends TestCase
{
    public function test_unexpected_device_name(): void
    {
        $data = [
            'id' => 4419625548,
            'name' => 'Coopertest',
            'type' => 'Run',
            'distance' => 2700,
            'timezone' => '(GMT+01:00) Europe/Berlin',
            'max_speed' => 0,
            'start_date' => '2020-12-02T10:08:09Z',
            'device_name' => false,
            'moving_time' => 720,
            'elapsed_time' => 720,
            'average_speed' => 3.75,
        ];

        $activity = Activity::fromRawData($data);

        self::assertEquals('<unknown>', $activity->getDeviceName());
    }

    public function test_end_date_is_based_on_elapsed_time_not_moving_time(): void
    {
        // Stream timestamps use elapsed time offsets (0 → elapsed_time).
        // The end date must match the last possible stream timestamp so that
        // ZoneSessions::finalize() closes the last zone session correctly.
        $data = [
            'id' => 1,
            'name' => 'Morning Run',
            'type' => 'Run',
            'distance' => 10000,
            'timezone' => '(GMT+01:00) Europe/Berlin',
            'max_speed' => 4.0,
            'start_date' => '2024-01-01T09:00:00Z',
            'device_name' => 'Garmin',
            'moving_time' => 3000, // 50 minutes of actual running
            'elapsed_time' => 3300, // 55 minutes wall-clock (5 min of pauses)
            'average_speed' => 3.0,
        ];

        $activity = Activity::fromRawData($data);

        $expected = $activity->getStartDateLocalTime()->add(\DateInterval::createFromDateString('3300 seconds'));

        self::assertEquals($expected, $activity->getEndDateLocalTime());
    }
}
