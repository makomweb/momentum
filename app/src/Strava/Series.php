<?php

declare(strict_types=1);

namespace App\Strava;

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

    /**
     * @param array{
     *     data: array<int|float>,
     *     series_type: string,
     *     resolution: string,
     *     original_size: int
     * } $array
     */
    public static function fromArray(array $array): self
    {
        return new self(
            data: $array['data'],
            seriesType: $array['series_type'],
            resolution: $array['resolution'],
            originalSize: $array['original_size'],
        );
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
