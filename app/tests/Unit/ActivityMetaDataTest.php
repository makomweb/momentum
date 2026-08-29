<?php

namespace App\Tests\Unit;

use App\Strava\ActivityMetaData;
use App\Strava\API\Activity;
use App\Twig\FormatMovingTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ActivityMetaDataTest extends TestCase
{
    #[Test]
    #[DataProvider('provideTestData')]
    public function format_moving_time(ActivityMetaData $data, string $expected): void
    {
        $actual = FormatMovingTime::format($data->getMovingTime());

        self::assertSame($expected, $actual);
    }

    /**
     * @return array<string, array{ActivityMetaData, string}>
     */
    public static function provideTestData(): array
    {
        return [
            '22s' => [self::create(['moving_time' => 22]), '22s'],
            '2min 3s' => [self::create(['moving_time' => 123]), '2min 3s'],
            '2min 55s' => [self::create(['moving_time' => 175]), '2min 55s'],
            '1h 30s' => [self::create(['moving_time' => 3630]), '1h 30s'],
            '2h 46min 40s' => [self::create(/* Rely on default value. */), '2h 46min 40s'],
            '3h 3min 20s' => [self::create(['moving_time' => 11000]), '3h 3min 20s'],
        ];
    }

    /**
     * @param array<string, int|float|string|bool> $data
     */
    private static function create(array $data = []): ActivityMetaData
    {
        $prototTypeData = [
            'id' => 123,
            'device_name' => 'Test-Device',
            'name' => 'Test Activity',
            'start_date' => (new \DateTimeImmutable())->format('c'),
            'timezone' => 'Germany/Berlin',
            'elapsed_time' => 10000,
            'moving_time' => 10000,
            'distance' => 0.0,
            'max_speed' => 0.0,
            'average_speed' => 0.0,
            'type' => 'Hike',
        ];

        /**
         * @var array{
         *  id: int,
         *  device_name: string|bool,
         *  name: string,
         *  start_date: string,
         *  timezone: string,
         *  elapsed_time: int,
         *  moving_time: int,
         *  distance: float,
         *  max_speed: float,
         *  average_speed: float,
         *  type: string
         * } $mergedData */
        $mergedData = [...$prototTypeData, ...$data];

        return new ActivityMetaData(Activity::fromRawData($mergedData));
    }
}
