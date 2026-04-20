<?php

declare(strict_types=1);

namespace App\Entity;

use App\Strava\API\Activity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'activity')]
#[ORM\UniqueConstraint(name: 'unique_strava_activity_id', columns: ['strava_activity_id', 'strava_user_id'])]
final class ActivityEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'integer')]
    private readonly int $stravaActivityId;

    #[ORM\Column(type: 'integer')]
    private readonly int $stravaUserId;

    #[ORM\Column(type: 'string', length: 255)]
    private readonly string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private readonly string $deviceName;

    #[ORM\Column(type: 'integer')]
    private readonly int $startDate;

    #[ORM\Column(type: 'string', length: 255)]
    private readonly string $timezone;

    #[ORM\Column(type: 'integer')]
    private readonly int $elapsedTime;

    #[ORM\Column(type: 'integer')]
    private readonly int $movingTime;

    #[ORM\Column(type: 'float')]
    private readonly float $distance;

    #[ORM\Column(type: 'float')]
    private readonly float $maxSpeed;

    #[ORM\Column(type: 'float')]
    private readonly float $averageSpeed;

    #[ORM\Column(type: 'string', length: 50)]
    private readonly string $type;

    #[ORM\Column(type: 'datetime_immutable')]
    private readonly \DateTimeImmutable $createdAt;

    /**
     * @param array{
     *  id: int,
     *  device_name: string,
     *  name: string,
     *  start_date: int|string,
     *  timezone: string,
     *  elapsed_time: int,
     *  moving_time: int,
     *  distance: float,
     *  max_speed: float,
     *  average_speed: float,
     *  type: string
     * } $activityData
     */
    private function __construct(
        int $stravaActivityId,
        int $stravaUserId,
        array $activityData,
    ) {
        $this->stravaActivityId = $stravaActivityId;
        $this->stravaUserId = $stravaUserId;
        $this->name = $activityData['name'];
        $this->deviceName = $activityData['device_name'];
        $this->startDate = is_string($activityData['start_date']) ? intval($activityData['start_date']) : $activityData['start_date'];
        $this->timezone = $activityData['timezone'];
        $this->elapsedTime = $activityData['elapsed_time'];
        $this->movingTime = $activityData['moving_time'];
        $this->distance = $activityData['distance'];
        $this->maxSpeed = $activityData['max_speed'];
        $this->averageSpeed = $activityData['average_speed'];
        $this->type = $activityData['type'];
        $this->createdAt = new \DateTimeImmutable();
    }

    public static function fromActivity(int $stravaUserId, Activity $activity): self
    {
        // Access the private data through reflection to get the raw values
        $reflection = new \ReflectionClass($activity);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);

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
        $data = $dataProperty->getValue($activity);

        return new self(
            $data['id'],
            $stravaUserId,
            $data,
        );
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStravaActivityId(): int
    {
        return $this->stravaActivityId;
    }

    public function getStravaUserId(): int
    {
        return $this->stravaUserId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDeviceName(): string
    {
        return $this->deviceName;
    }

    public function getStartDate(): int
    {
        return $this->startDate;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getElapsedTime(): int
    {
        return $this->elapsedTime;
    }

    public function getMovingTime(): int
    {
        return $this->movingTime;
    }

    public function getDistance(): float
    {
        return $this->distance;
    }

    public function getMaxSpeed(): float
    {
        return $this->maxSpeed;
    }

    public function getAverageSpeed(): float
    {
        return $this->averageSpeed;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
