<?php

declare(strict_types=1);

namespace App\Entity;

use App\Strava\API\Activity as StravaApiActivity;
use App\Strava\API\Speed;
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
        \DateTimeImmutable $startDateUtc,
        array $data,
    ) {
        $this->stravaUserId = $stravaUserId;
        $this->stravaActivityId = $stravaActivityId;
        $this->startDate = $startDateUtc;
        $this->data = $data;
    }

    public static function fromActivity(int $stravaUserId, StravaApiActivity $activity): self
    {
        return new self(
            $stravaUserId,
            $activity->getId(),
            $activity->getStartDateUniversalTime(),
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

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
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

    public function getSpeed(): Speed
    {
        return Speed::fromStrava((float) $this->data['average_speed'], $this->data['type']);
    }

    public function getStartDateLocalTime(): \DateTimeImmutable
    {
        $result = preg_match('/\)\s(.+)$/', $this->data['timezone'], $matches);
        if (1 !== $result) {
            // Fallback to UTC if timezone not available
            return $this->startDate;
        }

        $tz = new \DateTimeZone($matches[1]);

        return $this->startDate->setTimezone($tz);
    }
}
