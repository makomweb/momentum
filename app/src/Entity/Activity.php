<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activity')]
#[ORM\UniqueConstraint(name: 'unique_strava_activity_id', columns: ['strava_user_id', 'strava_activity_id'])]
final class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'integer')]
    private readonly int $stravaUserId;

    #[ORM\Column(type: 'bigint')]
    private readonly int $stravaActivityId;

    #[ORM\Column(type: 'integer', name: 'start_date')]
    private readonly int $startDate;

    /**
     * @var array{
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
    #[ORM\Column]
    private array $data;

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
    public function __construct(
        int $stravaUserId,
        int $stravaActivityId,
        array $data,
    ) {
        $this->stravaUserId = $stravaUserId;
        $this->stravaActivityId = $stravaActivityId;
        $this->startDate = $data['start_date'];
        $this->data = $data;
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

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    /**
     * @return array{
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
     * }
     */
    public function getData(): array
    {
        return $this->data;
    }
}
