<?php

declare(strict_types=1);

namespace App\RunForge;

use App\Strava\API\Assert;
use Stringable;

final readonly class MovingAverage implements Stringable
{
    private function __construct(
        public int $period,
        private Average $average,
        public array $values = []
    ) {        
    }

    public function clone(): self
    {
        return new self(
            $this->period,
            $this->average->clone(),
            $this->values
        );
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
