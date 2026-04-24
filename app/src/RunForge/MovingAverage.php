<?php

declare(strict_types=1);

namespace App\RunForge;

final class MovingAverage implements \Stringable
{
    /** @param float[] $values */
    private function __construct(
        private string $name,
        public int $period,
        private Average $average,
        public array $values = [],
    ) {
    }

    public static function create(string $name, \DateTimeImmutable $start, int $period): self
    {
        assert(
            $period > 0,
            new \InvalidArgumentException('Period must be greater than 0')
        );

        return new self($name, $period, new InitialAverage($start));
    }

    public function with(int|float $value, \DateTimeImmutable $timestamp): self
    {
        // push a new value into the buffer
        $values = [...$this->values, $value];

        // when there are less or equal values than the desired:
        if (count($values) <= $this->period) {
            // calculate simple average
            $average = SimpleAverage::create($values, $timestamp);
        } else {
            // otherwise remove oldest value + calculate exponential average
            array_shift($values);
            $average = ExponentialAverage::create($this->average, $values, $timestamp);
        }

        return new self($this->name, $this->period, $average, $values);
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->average->timestamp;
    }

    public function getName(): string
    {
        return sprintf('%s (EMA-%d)', $this->name, $this->period);
    }

    public function getValue(): float
    {
        // ignore warmup phase:
        return $this->average instanceof ExponentialAverage
            ? $this->average->value
            : 0.0;
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
