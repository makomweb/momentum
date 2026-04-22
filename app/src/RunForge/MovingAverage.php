<?php

declare(strict_types=1);

namespace App\RunForge;

use App\Assert\Assert;

final class MovingAverage implements \Stringable
{
    /** @param float[] $values */
    private function __construct(
        public int $period,
        private Average $average,
        public array $values = [],
    ) {
    }

    public static function create(\DateTimeImmutable $start, int $period): self
    {
        assert(
            $period > 0,
            new \InvalidArgumentException('Period must be greater than 0')
        );

        return new self($period, new InitialAverage($start));
    }

    public function clone(): self
    {
        return new self(
            $this->period,
            $this->average->clone(),
            $this->values
        );
    }

    /** @param array{value: float|int, time_stamp: \DateTimeImmutable} $data */
    public function withData(array $data): self
    {
        $value = $data['value'];
        $timestamp = $data['time_stamp'];

        return $this->withTimestamp($value, $timestamp);
    }

    public function withTimestamp(int|float $value, \DateTimeImmutable $timestamp): self
    {
        // push a new value into the buffer
        $values = [...$this->values, $value];

        // when there are less or equal values than the desired:
        if (count($values) <= $this->period) {
            // calculate simple average
            $average = Average::simple($values, $timestamp);
        } else {
            // otherwise remove oldest value + calculate exponential average
            array_shift($values);
            $average = Average::exponential($this->average, $values, $timestamp);
        }

        return new self($this->period, $average, $values);
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->average->timestamp;
    }

    public function getName(): string
    {
        return sprintf('(E)MA-%d', $this->period);
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
            '%s: %.2F (%s)',
            $this->getTimestamp()->format(\DateTimeImmutable::RFC2822),
            $this->getValue(),
            $this->getName(),
        );
    }
}
