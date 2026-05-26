<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\Assert\Assert;

final readonly class Activity implements \Stringable
{
    /**
     * @param array{
     *  id: int,
     *  device_name: string,
     *  name: string,
     *  start_date: string,
     *  timezone: string,
     *  elapsed_time: int,
     *  moving_time: int,
     *  distance: float,
     *  max_speed: float,
     *  average_speed: float,
     *  type: string
     * } $data
     */
    private function __construct(public array $data)
    {
    }

    /**
     * @param array{
     *  id: int,
     *  device_name: string|bool,
     *  name: string,
     *  start_date: string,
     *  timezone: string,
     *  elapsed_time: int,
     *  moving_time: int,
     *  distance: float,
     *  max_speed: float,
     *  average_speed: float,
     *  type: string
     * } $data
     */
    public static function fromRawData(array $data): self
    {
        return new self([
            'id' => $data['id'],
            'device_name' => is_string($data['device_name']) ? $data['device_name'] : '<unknown>',
            'name' => $data['name'],
            'start_date' => $data['start_date'],
            'timezone' => $data['timezone'],
            'elapsed_time' => $data['elapsed_time'],
            'moving_time' => $data['moving_time'],
            'distance' => $data['distance'],
            'max_speed' => $data['max_speed'],
            'average_speed' => $data['average_speed'],
            'type' => $data['type'],
        ]);
    }

    public function getId(): int
    {
        return $this->data['id'];
    }

    public function getDeviceName(): string
    {
        return $this->data['device_name'];
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getStartDateUniversalTime(): \DateTimeImmutable
    {
        $dateTime = new \DateTimeImmutable($this->data['start_date']);

        return $dateTime->setTimezone(new \DateTimeZone('UTC'));
    }

    public function getTimezone(): \DateTimeZone
    {
        $result = preg_match('/\)\s(.+)$/', $this->data['timezone'], $matches);
        Assert::that(1 === $result, 'There should be time zone information!');
        assert(1 === $result, 'There should be time zone information!');

        return new \DateTimeZone($matches[1]); // e.g. "Europe/Berlin"
    }

    public function getStartDateLocalTime(): \DateTimeImmutable
    {
        return $this
            ->getStartDateUniversalTime()
            ->setTimezone(
                $this->getTimeZone()
            );
    }

    public function getStartDateLocalTimeFormatted(): string
    {
        return $this->getStartDateLocalTime()->format('D M d, y · H:i');
    }

    public function getElapsedTime(): \DateInterval
    {
        $seconds = $this->data['elapsed_time'];

        return \DateInterval::createFromDateString("$seconds seconds");
    }

    public function getMovingTime(): \DateInterval
    {
        $seconds = $this->data['moving_time'];

        return \DateInterval::createFromDateString("$seconds seconds");
    }

    public function getDistance(): Distance
    {
        return new Distance($this->data['distance']);
    }

    public function getMaxVelocity(): Velocity
    {
        return new Velocity($this->data['max_speed']);
    }

    public function getAverageVelocity(): Velocity
    {
        return new Velocity($this->data['average_speed']);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s',
            $this->getName(),
            $this->getStartDateLocalTime()->format(\DateTimeInterface::RFC2822)
        );
    }
}
