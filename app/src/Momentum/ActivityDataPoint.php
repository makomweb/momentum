<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ActivityDataPoint implements \Stringable
{
    /**
     * @param array{
     *  timestamp: \DateTimeImmutable,
     *  distance: Distance,
     *  heart_rate: HeartRate,
     *  heart_rate_avg: HeartRate,
     *  velocity: Velocity,
     *  velocity_avg: Velocity,
     *  watts: Power,
     *  watts_avg: Power
     * } $data
     */
    public function __construct(private array $data)
    {
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->data['timestamp'];
    }

    public function getDistance(): Distance
    {
        return $this->data['distance'];
    }

    public function hasDistance(): bool
    {
        return !$this->getDistance()->isZero();
    }

    public function getHeartRate(): HeartRate
    {
        return $this->data['heart_rate'];
    }

    public function getHeartRateAverage(): HeartRate
    {
        return $this->data['heart_rate_avg'];
    }

    public function getVelocity(): Velocity
    {
        return $this->data['velocity'];
    }

    public function getVelocityAverage(): Velocity
    {
        return $this->data['velocity_avg'];
    }

    public function hasVelocity(): bool
    {
        return !$this->getVelocity()->isZero()
            && !$this->getVelocityAverage()->isZero();
    }

    public function getWatts(): Power
    {
        return $this->data['watts'];
    }

    public function getWattsAverage(): Power
    {
        return $this->data['watts_avg'];
    }

    public function getPaceZone(Pace $treshold): PaceZone
    {
        return $this->getVelocityAverage()->asPace()->getZone($treshold);
    }

    public function getHeartRateZone(HeartRate $maxHeartRate): HeartRateZone
    {
        return $this->getHeartRate()->getZone($maxHeartRate);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s · %s · %s',
            $this->getTimestamp()->format('D M d, y · H:i:s'),
            $this->getVelocity()->asPace(),
            $this->getHeartRate()
        );
    }
}
