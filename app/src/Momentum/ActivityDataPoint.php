<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ActivityDataPoint
{
    /**
     * @param array{
     *  timestamp: \DateTimeImmutable,
     *  distance: float|null,
     *  heart_rate: HeartRate|null,
     *  heart_rate_avg: HeartRate|null,
     *  velocity: Velocity|null,
     *  velocity_avg: Velocity|null,
     * } $data
     */
    public function __construct(private array $data)
    {
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->data['timestamp'];
    }

    public function getDistance(): ?float
    {
        return $this->data['distance'];
    }

    public function getHeartRate(): HeartRate
    {
        $hr = $this->data['heart_rate'];

        return $hr ?? new HeartRate();
    }

    public function getHeartRateAverage(): HeartRate
    {
        $hr = $this->data['heart_rate_avg'];

        return $hr ?? new HeartRate();
    }

    public function hasVelocity(): bool
    {
        return !$this->getVelocity()->isZero()
            && !$this->getVelocityAverage()->isZero();
    }

    public function getVelocity(): Velocity
    {
        $velocity = $this->data['velocity'];

        return $velocity ?? new Velocity();
    }

    public function getVelocityAverage(): Velocity
    {
        $velocity = $this->data['velocity_avg'];

        return $velocity ?? new Velocity();
    }
}
