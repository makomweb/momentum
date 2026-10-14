<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Twig\ActivityFilter;
use PHPUnit\Framework\TestCase;

class ActivityFilterTest extends TestCase
{
    public function test_me(): void
    {
        $activity = ActivityDataFactory::createActivityData(14417573, 19881882510);

        $result = ActivityFilter::getMinVelocity($activity);

        self::fail('Foobar!');
    }
}
