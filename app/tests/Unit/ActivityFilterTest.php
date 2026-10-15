<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Twig\ActivityFilter;
use PHPUnit\Framework\TestCase;

class ActivityFilterTest extends TestCase
{
    public function test_me(): void
    {
        $activity = ActivityDataFactory::createActivityData(14417573, 20531248086);

        $result = ActivityFilter::getMinVelocity($activity);

        dump($result);

        self::fail('Foobar!');
    }
}
