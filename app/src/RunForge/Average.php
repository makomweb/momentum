<?php

declare(strict_types=1);

namespace App\RunForge;

class Average implements \Stringable
{
    protected function __construct(
        public \DateTimeImmutable $timestamp,
        public float $value,
    ) {
    }

    public function clone(): self
    {
        return new self($this->timestamp, $this->value);
    }

    /**
     * @param float[] $values
     */
    public static function simple(array $values, \DateTimeImmutable $timestamp): Average
    {
        assert(!empty($values), new NotEnoughValuesException());

        $average = array_sum($values) / count($values);

        return new self($timestamp, round($average, 2));
    }

    /**
     * @param float[] $values
     */
    public static function exponential(Average $previous, array $values, \DateTimeImmutable $timestamp): Average
    {
        assert(!empty($values), new NotEnoughValuesException());

        if ($previous instanceof InitialAverage) {
            return self::simple($values, $timestamp);
        }

        $last = array_last($values);
        $multiplier = 2 / (count($values) + 1);
        $average = $last * $multiplier + $previous->value * (1 - $multiplier);

        return new self($timestamp, round($average, 2));
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: AVG: %.2F',
            $this->timestamp->format(\DateTimeInterface::RFC2822),
            $this->value,
        );
    }
}
