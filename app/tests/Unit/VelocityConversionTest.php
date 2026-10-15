<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\SpeedUnit;
use App\Momentum\Velocity;
use PHPUnit\Framework\TestCase;

class VelocityConversionTest extends TestCase
{
    public function test_me(): void
    {
        $values = [2.0, 2.1, 2.2, 2.9, 0.0, 2.4];
        $min = Velocity::min($values);
        $max = Velocity::max($values);

        dump([
            'min' => (string) $min->asSpeed(SpeedUnit::METERS_PER_SECOND),
            'max' => (string) $max->asSpeed(SpeedUnit::METERS_PER_SECOND),
            'min_pace' => (string) $min->asPace(),
            'max_pace' => (string) $max->asPace()
        ]);

        self::fail('foobar');
    }
}