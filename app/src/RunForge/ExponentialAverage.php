<?php

declare(strict_types=1);

namespace App\RunForge;

class ExponentialAverage extends Average
{
    /**
     * @param float[] $values
     */
    public static function create(Average $previous, array $values, \DateTimeImmutable $timestamp): Average
    {
        assert(!empty($values), new NotEnoughValuesException());

        if ($previous instanceof InitialAverage) {
            return SimpleAverage::create($values, $timestamp);
        }

        $last = array_last($values);
        $multiplier = 2 / (count($values) + 1);
        $average = $last * $multiplier + $previous->value * (1 - $multiplier);

        return new self($timestamp, round($average, 2));
    }
}
