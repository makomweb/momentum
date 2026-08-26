<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\ActivityDataPoint;
use App\Momentum\HeartRate;
use App\Momentum\MovingAverage;
use App\Momentum\Power;
use App\Momentum\Velocity;

final readonly class ActivityStream
{
    public function __construct(
        public ?Series $times = null,
        public ?Series $distances = null,
        public ?Series $velocities = null,
        public ?Series $heartrates = null,
        public ?Series $watts = null,
    ) {
    }

    public function hasHeartRateData(): bool
    {
        return null !== $this->heartrates && $this->heartrates->getCount() > 0;
    }

    public function hasTimeData(): bool
    {
        return null !== $this->times && $this->times->getCount() > 0;
    }

    public function hasDistanceData(): bool
    {
        return null !== $this->distances && $this->distances->getCount() > 0;
    }

    public function hasVelocityData(): bool
    {
        return null !== $this->velocities && $this->velocities->getCount() > 0;
    }

    public function hasWattData(): bool
    {
        return null !== $this->watts && $this->watts->getCount() > 0;
    }

    public function hasData(): bool
    {
        return $this->hasHeartRateData()
            || $this->hasTimeData()
            || $this->hasDistanceData()
            || $this->hasVelocityData()
            || $this->hasWattData();
    }

    /**
     * @return \Generator<ActivityDataPoint>
     */
    public function yield(\DateTimeImmutable $start, int $movingAveragePeriod): \Generator
    {
        assert($this->hasTimeData());
        assert(!is_null($this->times));

        $velocityAvg = $this->hasVelocityData() ? MovingAverage::create('velocity_avg', $start, $movingAveragePeriod) : null;
        $heartRateAvg = $this->hasHeartRateData() ? MovingAverage::create('heart_rate_avg', $start, $movingAveragePeriod) : null;
        $wattsAvg = $this->hasWattData() ? MovingAverage::create('watts_avg', $start, $movingAveragePeriod) : null;

        $count = $this->times->getCount();

        for ($i = 0; $i < $count; ++$i) {
            $offset = $this->times->getDataPoint($i);
            assert(is_int($offset));

            $timestamp = $start->add(
                \DateInterval::createFromDateString("$offset seconds")
            );

            $distance = null;
            if ($this->hasDistanceData() && null !== $this->distances) {
                $distanceValue = $this->distances->getDataPoint($i);
                assert(is_numeric($distanceValue));
                $distance = $distanceValue;
            }

            $velocity = null;
            $velocityAvgValue = null;
            if ($this->hasVelocityData() && null !== $this->velocities && null !== $velocityAvg) {
                $velocityValue = $this->velocities->getDataPoint($i);
                assert(is_numeric($velocityValue));
                $velocity = new Velocity($velocityValue);
                $velocityAvg = $velocityAvg->with($velocityValue, $timestamp);
                $velocityAvgValue = new Velocity($velocityAvg->getValue());
            }

            $heartRate = null;
            $heartRateAvgValue = null;
            if ($this->hasHeartRateData() && null !== $this->heartrates && null !== $heartRateAvg) {
                $heartRateValue = $this->heartrates->getDataPoint($i);
                assert(is_int($heartRateValue));
                $heartRate = new HeartRate($heartRateValue);
                $heartRateAvg = $heartRateAvg->with($heartRateValue, $timestamp);
                $heartRateAvgValue = new HeartRate((int) $heartRateAvg->getValue());
            }

            $watts = null;
            $wattsAvgValue = null;
            if ($this->hasWattData() && null !== $this->watts && null !== $wattsAvg) {
                $wattsValue = $this->watts->getDataPoint($i);
                assert(is_int($wattsValue));
                $watts = new Power($wattsValue);
                $wattsAvg = $wattsAvg->with($wattsValue, $timestamp);
                $wattsAvgValue = new Power((int) $wattsAvg->getValue());
            }

            yield new ActivityDataPoint([
                'timestamp' => $timestamp,
                'distance' => $distance,
                'heart_rate' => $heartRate,
                'heart_rate_avg' => $heartRateAvgValue,
                'velocity' => $velocity,
                'velocity_avg' => $velocityAvgValue,
                'watts' => $watts,
                'watts_avg' => $wattsAvgValue,
            ]);
        }
    }
}
