<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ActivityDataPoint implements \Stringable
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

        return $hr ?? HeartRate::ZERO();
    }

    public function getHeartRateAverage(): HeartRate
    {
        $hr = $this->data['heart_rate_avg'];

        return $hr ?? HeartRate::ZERO();
    }

    public function hasVelocity(): bool
    {
        return !$this->getVelocity()->isZero()
            && !$this->getVelocityAverage()->isZero();
    }

    public function getVelocity(): Velocity
    {
        $velocity = $this->data['velocity'];

        return $velocity ?? Velocity::ZERO();
    }

    public function getVelocityAverage(): Velocity
    {
        $velocity = $this->data['velocity_avg'];

        return $velocity ?? Velocity::ZERO();
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
