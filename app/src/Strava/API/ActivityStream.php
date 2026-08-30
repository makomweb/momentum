<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\ActivityDataPoint;
use App\Momentum\Distance;
use App\Momentum\HeartRate;
use App\Momentum\MovingAverage;
use App\Momentum\Power;
use App\Momentum\Velocity;

final readonly class ActivityStream
{
    public function __construct(
        public Series $time,
        public Series $distance,
        public Series $velocity,
        public Series $heartrate,
        public Series $watts,
    ) {
    }

    public function hasHeartRateData(): bool
    {
        return $this->heartrate->getCount() > 0;
    }

    public function hasTimeData(): bool
    {
        return $this->time->getCount() > 0;
    }

    public function hasDistanceData(): bool
    {
        return $this->distance->getCount() > 0;
    }

    public function hasVelocityData(): bool
    {
        return $this->velocity->getCount() > 0;
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

        $count = $this->time->getCount();

        for ($i = 0; $i < $count; ++$i) {
            $offset = $this->time->getDataPoint($i);
            assert(is_int($offset));

            $timestamp = $start->add(
                \DateInterval::createFromDateString("$offset seconds")
            );

            $distance = Distance::ZERO();
            if ($this->hasDistanceData()) {
                $distanceValue = $this->distance->getDataPoint($i);
                assert(is_numeric($distanceValue));
                $distance = new Distance($distanceValue);
            }

            $velocity = Velocity::ZERO();
            $velocityAvgValue = Velocity::ZERO();
            if ($this->hasVelocityData()) {
                $velocityValue = $this->velocity->getDataPoint($i);
                assert(is_numeric($velocityValue));
                $velocity = new Velocity($velocityValue);
                $velocityAvg = $velocityAvg->with($velocityValue, $timestamp);
                $velocityAvgValue = new Velocity($velocityAvg->getValue());
            }

            $heartRate = HeartRate::ZERO();
            $heartRateAvgValue = HeartRate::ZERO();
            if ($this->hasHeartRateData()) {
                $heartRateValue = $this->heartrate->getDataPoint($i);
                assert(is_int($heartRateValue));
                $heartRate = new HeartRate($heartRateValue);
                $heartRateAvg = $heartRateAvg->with($heartRateValue, $timestamp);
                $heartRateAvgValue = new HeartRate((int) $heartRateAvg->getValue());
            }

            $watts = Power::ZERO();
            $wattsAvgValue = Power::ZERO();
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
