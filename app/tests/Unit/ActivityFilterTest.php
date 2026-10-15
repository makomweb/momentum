<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Strava\ActivityData;
use App\Twig\ActivityFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Twig\TwigFilter;

class ActivityFilterTest extends TestCase
{
    #[Test]
    public function calculate_pace_chart_bounds_from_smoothed_points(): void
    {
        $activity = ActivityDataFactory::createActivityData(14417573, 20531248086);

        $min = ActivityFilter::getMinVelocity($activity);
        $max = ($this->getFilterCallable('y_axis_max'))($activity);

        self::assertEqualsWithDelta(5.51, $min, 0.05);
        self::assertEqualsWithDelta(6.44, $max, 0.05);
        self::assertLessThan($max, $min);
    }

    /**
     * @return callable(ActivityData): float
     */
    private function getFilterCallable(string $name): callable
    {
        $callable = $this->getFilter($name)->getCallable();
        assert(is_callable($callable));

        return static function (ActivityData $activity) use ($callable): float {
            $value = $callable($activity);
            assert(is_float($value));

            return $value;
        };
    }

    private function getFilter(string $name): TwigFilter
    {
        foreach ((new ActivityFilter())->getFilters() as $filter) {
            if ($filter->getName() === $name) {
                return $filter;
            }
        }

        self::fail(sprintf('Expected filter "%s" to exist.', $name));
    }
}
