<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\MovingAverage;

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
            velocity: isset($data['velocity_smooth'])
                ? Series::fromArray($data['velocity_smooth'])
                : (isset($data['velocity']) ? Series::fromArray($data['velocity']) : null),
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

    /**
     * @return \Generator<array{
     *  timestamp: \DateTimeImmutable,
     *  distance: float,
     *  heart_rate: HeartFrequency,
     *  heart_rate_avg: HeartFrequency,
     *  velocity: Velocity,
     *  velocity_avg: Velocity,
     * }>
     */
    public function yield(\DateTimeImmutable $start, int $period): \Generator
    {
        assert($this->hasTimeData());

        assert(!is_null($this->time));
        assert(!is_null($this->distance));
        assert(!is_null($this->velocity));
        assert(!is_null($this->heartrate));

        assert($this->hasDistanceData());
        assert($this->hasVelocityData());
        assert($this->hasHeartRateData());

        assert($this->time->getCount() == $this->distance->getCount());
        assert($this->time->getCount() == $this->velocity->getCount());
        assert($this->time->getCount() == $this->heartrate->getCount());

        $velocityAvg = MovingAverage::create('velocity_avg', $start, $period);
        $heartRateAvg = MovingAverage::create('heart_rate_avg', $start, $period);

        for ($i = 0; $i < $this->time->getCount(); ++$i) {
            $offset = $this->time->getDataPoint($i);
            $distance = $this->distance->getDataPoint($i);
            $velocity = $this->velocity->getDataPoint($i);
            $heartRate = $this->heartrate->getDataPoint($i);

            assert(is_int($offset));
            $offset = \DateInterval::createFromDateString("$offset seconds");
            assert($offset instanceof \DateInterval);

            assert(is_numeric($distance));
            assert(is_numeric($velocity));
            assert(is_int($heartRate));

            $timestamp = $start->add($offset);

            $heartRateAvg = $heartRateAvg->with($heartRate, $timestamp);
            $velocityAvg = $velocityAvg->with($velocity, $timestamp);

            yield [
                'timestamp' => $timestamp,
                'distance' => $distance,
                'heart_rate' => new HeartFrequency($heartRate),
                'heart_rate_avg' => new HeartFrequency((int) $heartRateAvg->getValue()),
                'velocity' => new Velocity($velocity),
                'velocity_avg' => new Velocity($velocityAvg->getValue()),
            ];
        }
    }
}
