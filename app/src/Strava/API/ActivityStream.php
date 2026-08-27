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
        public Series $times,
        public Series $distances,
        public Series $velocities,
        public Series $heartrates,
        public Series $watts,
    ) {
    }

    public function hasHeartRateData(): bool
    {
        return $this->heartrates->getCount() > 0;
    }

    public function hasTimeData(): bool
    {
        return $this->times->getCount() > 0;
    }

    public function hasDistanceData(): bool
    {
        return $this->distances->getCount() > 0;
    }

    public function hasVelocityData(): bool
    {
        return $this->velocities->getCount() > 0;
    }

    public function hasWattData(): bool
    {
        return $this->watts->getCount() > 0;
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
    public function yield(\DateTimeImmutable $start, int $movingAveragePeriod, int $wattsAveragePeriod = 4): \Generator
    {
        assert($this->hasTimeData());

        $velocityAvg = MovingAverage::create('velocity_avg', $start, $movingAveragePeriod);
        $heartRateAvg = MovingAverage::create('heart_rate_avg', $start, $movingAveragePeriod);
        $wattsAvg = MovingAverage::create('watts_avg', $start, $wattsAveragePeriod);

        $count = $this->times->getCount();

        for ($i = 0; $i < $count; ++$i) {
            $offset = $this->times->getDataPoint($i);
            assert(is_int($offset));

            $timestamp = $start->add(
                \DateInterval::createFromDateString("$offset seconds")
            );

            $distance = null;
            if ($this->hasDistanceData()) {
                $distanceValue = $this->distances->getDataPoint($i);
                assert(is_numeric($distanceValue));
                $distance = $distanceValue;
            }

            $velocity = null;
            $velocityAvgValue = null;
            if ($this->hasVelocityData()) {
                $velocityValue = $this->velocities->getDataPoint($i);
                assert(is_numeric($velocityValue));
                $velocity = new Velocity($velocityValue);
                $velocityAvg = $velocityAvg->with($velocityValue, $timestamp);
                $velocityAvgValue = new Velocity($velocityAvg->getValue());
            }

            $heartRate = null;
            $heartRateAvgValue = null;
            if ($this->hasHeartRateData()) {
                $heartRateValue = $this->heartrates->getDataPoint($i);
                assert(is_int($heartRateValue));
                $heartRate = new HeartRate($heartRateValue);
                $heartRateAvg = $heartRateAvg->with($heartRateValue, $timestamp);
                $heartRateAvgValue = new HeartRate((int) $heartRateAvg->getValue());
            }

            $watts = null;
            $wattsAvgValue = null;
            if ($this->hasWattData()) {
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
