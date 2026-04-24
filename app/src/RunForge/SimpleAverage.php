<?php

declare(strict_types=1);

namespace App\RunForge;

final class SimpleAverage extends Average
{
    /**
     * @param float[] $values
     */
    public static function create(array $values, \DateTimeImmutable $timestamp): self
    {
        assert(!empty($values), new NotEnoughValuesException());

        $average = array_sum($values) / count($values);

        return new self($timestamp, round($average, 2));
    }
}
