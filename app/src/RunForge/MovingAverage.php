<?php

declare(strict_types=1);

namespace App\RunForge;

use App\Assert\Assert;

final class MovingAverage implements \Stringable
{
    /** @param array<int|float> $values */
    private function __construct(
        public int $period,
        private Average $average,
        public array $values = [],
    ) {
    }

    public static function ema(int $period): self
    {
        Assert::that(
            $period > 0,
            new \InvalidArgumentException('Period must be greater than 0')
        );

        return new self($period, InitialAverage::create());
    }

    public static function sma(int $period): self
    {
        return self::ema($period);
    }

    public function clone(): self
    {
        return new self(
            $this->period,
            $this->average->clone(),
            $this->values
        );
    }

    public function next(int|float $value, int $timeOffsetSeconds): self
    {
        $this->values[] = $value;

        if (count($this->values) > $this->period) {
            array_shift($this->values);
        }

        if (count($this->values) >= $this->period) {
            $numericValues = $this->values;
            $this->average = Average::exponential($this->average, $numericValues, $timeOffsetSeconds);
        }

        return $this;
    }

    public function getName(): string
    {
        return sprintf('EMA-%d', $this->period);
    }

    public function getValue(): float
    {
        Assert::that(
            !$this->average instanceof InitialAverage,
            new \InvalidArgumentException('Should not be initial. Aggregate some candles first!')
        );

        return $this->average->value;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s: %.2F',
            $this->getName(),
            $this->getValue()
        );
    }
}
