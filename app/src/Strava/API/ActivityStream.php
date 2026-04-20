<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class ActivityStream
{
    public function __construct(
        public ?Series $time = null,
        public ?Series $distance = null,
        public ?Series $velocity = null,
        public ?Series $heartrate = null,
    ) {
    }

    public static function fromJsonFile(string $filePath): self
    {
        $content = file_get_contents($filePath);
        if (false === $content) {
            throw new \RuntimeException(sprintf('Failed to read file: %s', $filePath));
        }

        /**
         * @var array<string, array{
         *     data: (int|float)[],
         *     series_type: string,
         *     resolution: string,
         *     original_size: int
         * }> $data
         */
        $data = json_decode($content, true, flags: JSON_THROW_ON_ERROR);

        return ActivityStream::fromArray($data);
    }

    /**
     * @param array<string, array{
     *     data: (int|float)[],
     *     series_type: string,
     *     resolution: string,
     *     original_size: int
     * }> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            time: isset($data['time']) ? Series::fromArray($data['time']) : null,
            distance: isset($data['distance']) ? Series::fromArray($data['distance']) : null,
            velocity: isset($data['velocity_smooth']) ? Series::fromArray($data['velocity_smooth']) : null,
            heartrate: isset($data['heartrate']) ? Series::fromArray($data['heartrate']) : null,
        );
    }

    public function hasHeartRateData(): bool
    {
        return null !== $this->heartrate && $this->heartrate->getCount() > 0;
    }

    public function hasTimeData(): bool
    {
        return null !== $this->time && $this->time->getCount() > 0;
    }

    public function hasDistanceData(): bool
    {
        return null !== $this->distance && $this->distance->getCount() > 0;
    }

    public function hasVelocityData(): bool
    {
        return null !== $this->velocity && $this->velocity->getCount() > 0;
    }

    public function hasData(): bool
    {
        return $this->hasHeartRateData()
            || $this->hasTimeData()
            || $this->hasDistanceData()
            || $this->hasVelocityData();
    }
}
