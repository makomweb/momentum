<?php

namespace App\Tests\Unit;

use App\Strava\Distance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DistanceTest extends TestCase
{
    #[DataProvider('provideTestData')]
    public function test_string_representation(Distance $given, string $expected): void
    {
        $actual = sprintf('%s', $given);

        self::assertEquals($expected, $actual);
    }

    /** @return array<Distance, string>[] */
    public static function provideTestData(): array
    {
        return [
            [new Distance(991.0), '991.0 m'],
            [new Distance(991.04), '991.0 m'],
            [new Distance(991.05), '991.0 m'],
            [new Distance(991.06), '991.1 m'],
            [new Distance(1234.0), '1.23 km'],
            [new Distance(1235.0), '1.24 km'],
            [new Distance(1236.0), '1.24 km'],
        ];
    }
}
