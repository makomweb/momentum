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
}
