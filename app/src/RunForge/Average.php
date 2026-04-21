<?php

declare(strict_types=1);

namespace App\RunForge;

use Stringable;

class Average implements Stringable
{
    protected function __construct(
        public int $timeOffsetSeconds,
        public int|float $value 
    ) {}

    public function clone(): self
    {
        return new self($this->timeOffsetSeconds, $this->value);
    }
    
    /**
     * @param int|float[] $values
     */
    public static function exponential(Average $previous, array $values): Average
    {
        if ($previous instanceof InitialAverage) {
            return self::simple($values);
        }

        assert(!empty($values), new NotEnoughValuesException());

        $multiplier = 2 / (count($values) + 1);
        $last = $values[array_key_last($values)];
        $average = $last * $multiplier + $previous->value * (1 - $multiplier);

        return new self($last->timestamp, round($average, 2));
    }

    /**
     * @param int|float[] $values
     */
    public static function simple(array $values): Average
    {
        assert(!empty($values), new NotEnoughValuesException());

        $last = $values[array_key_last($values)];

        $average = array_sum($values) / count($values);

        return new self($last->timestamp, round($average, 2));
    }

    public function __toString(): string
    {
        return sprintf(
            is_int($this->value)
                ? '%d: AVG: %d'
                : '%d: AVG: %.2F',
            $this->timeOffsetSeconds,
            $this->value,
        );
    }
}
