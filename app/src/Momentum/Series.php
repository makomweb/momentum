<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Series
{
    /**
     * @param (int|float)[] $data
     */
    public function __construct(
        public array $data,
        public string $seriesType,
        public string $resolution,
        public int $originalSize,
    ) {
    }

    public static function empty(string $name): self
    {
        return new self([], $name, '<unknown>', 0);
    }

    public function getDataPoint(int $index): int|float|null
    {
        return $this->data[$index] ?? null;
    }

    public function getCount(): int
    {
        return count($this->data);
    }
}
