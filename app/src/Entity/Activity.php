<?php

declare(strict_types=1);

namespace App\Entity;

use App\Momentum\SpeedUnit;
use App\Momentum\Velocity;
use App\Strava\API\Activity as StravaApiActivity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activities')]
#[ORM\UniqueConstraint(name: 'unique_strava_activity_id', columns: ['strava_user_id', 'strava_activity_id'])]
final class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(unique: true)]
    private int $id = 0;

    #[ORM\Column]
    private readonly int $stravaUserId;

    #[ORM\Column(type: 'bigint')]
    private readonly int $stravaActivityId;

    #[ORM\Column]
    private readonly \DateTimeImmutable $startDate;

    #[ORM\Column(type: 'string')]
    private readonly string $timezone;

    /**
     * @var array{
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
    #[ORM\Column]
    private array $data;

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
    private function __construct(
        int $stravaUserId,
        int $stravaActivityId,
        \DateTimeImmutable $startDate,
        string $timezone,
        array $data,
    ) {
        $this->stravaUserId = $stravaUserId;
        $this->stravaActivityId = $stravaActivityId;
        $this->startDate = $startDate;
        $this->timezone = $timezone;
        $this->data = $data;
    }

    public static function fromActivity(int $stravaUserId, StravaApiActivity $activity): self
    {
        return new self(
            $stravaUserId,
            $activity->getId(),
            $activity->getStartDateUniversalTime(),
            $activity->getTimezone()->getName(),
            $activity->data
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStravaUserId(): int
    {
        return $this->stravaUserId;
    }

    public function getStravaActivityId(): int
    {
        return $this->stravaActivityId;
    }

    /**
     * @return array{
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
     * }
     */
    public function getData(): array
    {
        return $this->data;
    }

    public function getAverageVelocity(): Velocity
    {
        return new Velocity($this->data['average_speed']);
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getStartDateLocalTime(): \DateTimeImmutable
    {
        return $this->startDate->setTimezone(new \DateTimeZone($this->timezone));
    }

    public function getStartDateFormatted(): string
    {
        return $this->getStartDateLocalTime()->format('D M d, y · H:i');
    }

    public function getAverageVelocityAsStringable(): \Stringable
    {
        $type = $this->data['type'];

        return match ($type) {
            'Run', 'Walk', 'Hike', 'Swim' => $this->getAverageVelocity()->asPace(),
            'Ride' => $this->getAverageVelocity()->asSpeed()->as(SpeedUnit::KILOMETER_PER_HOUR),
            default => throw new \Exception(sprintf('Unsupported type "%s"!', $type)),
        };
    }
}
