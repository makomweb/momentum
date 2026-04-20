<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class Activity implements \Stringable
{
    /**
     * @param array{
     *  id: int,
     *  device_name: string,
     *  name: string,
     *  start_date: int,
     *  timezone: string,
     *  elapsed_time: int,
     *  moving_time: int,
     *  distance: float,
     *  max_speed: float,
     *  average_speed: float,
     *  type: string
     * } $data
     */
    public function __construct(private array $data)
    {
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

    public function getStartTime(): \DateTimeImmutable
    {
        $result = preg_match('/\)\s(.+)$/', $this->data['timezone'], $matches);
        Assert::that(1 === $result, 'There should be time zone information!');
        assert(1 === $result, 'There should be time zone information!');

        $tz = new \DateTimeZone($matches[1]); // e.g. "Europe/Berlin"

        // start_date is UTC: make it local time!
        return (new \DateTimeImmutable($this->data['start_date'].''))->setTimezone($tz);
    }

    public function getElapsedTime(): \DateInterval
    {
        $seconds = $this->data['elapsed_time'];
        $interval = \DateInterval::createFromDateString("$seconds seconds");
        assert($interval instanceof \DateInterval);

        return $interval;
    }

    public function getMovingTime(): \DateInterval
    {
        $seconds = $this->data['moving_time'];
        $interval = \DateInterval::createFromDateString("$seconds seconds");
        assert($interval instanceof \DateInterval);

        return $interval;
    }

    public function getDistance(): Distance
    {
        return new Distance($this->data['distance']);
    }

    public function getMaxSpeed(): Speed
    {
        return Speed::fromStrava($this->data['max_speed'], $this->data['type' /* or: sport_type */]);
    }

    public function getAverageSpeed(): Speed
    {
        return Speed::fromStrava($this->data['average_speed'], $this->data['type' /* or: sport_type */]);
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s',
            $this->getName(),
            $this->getStartTime()->format(\DateTimeInterface::RFC2822)
        );
    }
}
