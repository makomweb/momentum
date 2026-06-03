<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\ActivityDataPoint;
use App\Momentum\HeartRate;
use App\Momentum\MovingAverage;
use App\Momentum\Velocity;

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

        return self::fromJsonString($content);
    }

    public static function fromJsonString(string $json): self
    {
        /**
         * @var array<string, array{
         *     data: (int|float)[],
         *     series_type: string,
         *     resolution: string,
         *     original_size: int
         * }> $data
         */
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

        return self::fromArray($data);
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
     * @return \Generator<ActivityDataPoint>
     */
    public function yield(\DateTimeImmutable $start, int $movingAveragePeriod): \Generator
    {
        assert($this->hasTimeData());
        assert(!is_null($this->time));

        $velocityAvg = $this->hasVelocityData() ? MovingAverage::create('velocity_avg', $start, $movingAveragePeriod) : null;
        $heartRateAvg = $this->hasHeartRateData() ? MovingAverage::create('heart_rate_avg', $start, $movingAveragePeriod) : null;

        $count = $this->time->getCount();

        for ($i = 0; $i < $count; ++$i) {
            $offset = $this->time->getDataPoint($i);
            assert(is_int($offset));

            $timestamp = $start->add(
                \DateInterval::createFromDateString("$offset seconds")
            );

            $distance = null;
            if ($this->hasDistanceData() && null !== $this->distance) {
                $distanceValue = $this->distance->getDataPoint($i);
                assert(is_numeric($distanceValue));
                $distance = $distanceValue;
            }

            $velocity = null;
            $velocityAvgValue = null;
            if ($this->hasVelocityData() && null !== $this->velocity && null !== $velocityAvg) {
                $velocityValue = $this->velocity->getDataPoint($i);
                assert(is_numeric($velocityValue));
                $velocity = new Velocity($velocityValue);
                $velocityAvg = $velocityAvg->with($velocityValue, $timestamp);
                $velocityAvgValue = new Velocity($velocityAvg->getValue());
            }

            $heartRate = null;
            $heartRateAvgValue = null;
            if ($this->hasHeartRateData() && null !== $this->heartrate && null !== $heartRateAvg) {
                $heartRateValue = $this->heartrate->getDataPoint($i);
                assert(is_int($heartRateValue));
                $heartRate = new HeartRate($heartRateValue);
                $heartRateAvg = $heartRateAvg->with($heartRateValue, $timestamp);
                $heartRateAvgValue = new HeartRate((int) $heartRateAvg->getValue());
            }

            yield new ActivityDataPoint([
                'timestamp' => $timestamp,
                'distance' => $distance,
                'heart_rate' => $heartRate,
                'heart_rate_avg' => $heartRateAvgValue,
                'velocity' => $velocity,
                'velocity_avg' => $velocityAvgValue,
            ]);
        }
    }
}
