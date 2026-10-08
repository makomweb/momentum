<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\Activity as MomentumActivity;
use App\Momentum\ActivityDataPoint;
use App\Momentum\ActivityStream;
use App\Momentum\Assert\Assert;
use App\Momentum\AthleteInterface;
use App\Momentum\HeartRateZone;
use App\Momentum\PaceUnit;
use App\Momentum\PaceZone;
use App\Momentum\Power;
use App\Momentum\Session;
use App\Momentum\SpeedUnit;
use App\Momentum\Statistics;
use App\Momentum\TrainingMetrics;
use App\Momentum\Velocity;
use App\Strava\API\Activity;

final class ActivityData
{
    private const MOVING_AVERAGE_PERIOD = 50;

    public function __construct(
        private AthleteInterface $athlete,
        private Activity $activity,
        private ActivityStream $stream,
    ) {
    }

    public function getMetaData(): ActivityMetaData
    {
        return new ActivityMetaData($this->activity);
    }

    /**
     * @return ActivityDataPoint[]
     */
    public function getDataPoints(int $averagePeriod = self::MOVING_AVERAGE_PERIOD): array
    {
        return iterator_to_array($this->yieldDataPoints($averagePeriod));
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityRawPoints(int $averagePeriod = self::MOVING_AVERAGE_PERIOD): array
    {
        return iterator_to_array($this->yieldVelocityRawPoints($averagePeriod));
    }

    /**
     * @return array<array{timestamp: string, value: float}>
     */
    public function getVelocityAveragePoints(int $averagePeriod = self::MOVING_AVERAGE_PERIOD): array
    {
        return iterator_to_array($this->yieldVelocityAveragePoints($averagePeriod));
    }

    /**
     * @return array<array{timestamp: string, value: int, value_smooth: int}>
     */
    public function getWattPoints(): array
    {
        return iterator_to_array($this->yieldWattsPoints());
    }

    /**
     * @return \Generator<ActivityDataPoint>
     */
    private function yieldDataPoints(int $averagePeriod, int $wattsAveragePeriod = 4): \Generator
    {
        yield from $this->stream->yield($this->activity->getStartDateLocalTime(), $averagePeriod, $wattsAveragePeriod);
    }

    /**
     * @return \Generator<array{timestamp: string, value: float}>
     */
    private function yieldVelocityRawPoints(int $averagePeriod): \Generator
    {
        foreach ($this->yieldDataPoints($averagePeriod) as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->hasVelocity()
                    ? $this->convertVelocity($point->getVelocity())
                    : 0.0,
            ];
        }
    }

    /**
     * @return \Generator<array{timestamp: string, value: float}>
     */
    private function yieldVelocityAveragePoints(int $averagePeriod): \Generator
    {
        foreach ($this->yieldDataPoints($averagePeriod) as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->hasVelocity()
                    ? $this->convertVelocity($point->getVelocityAverage())
                    : 0.0,
            ];
        }
    }

    /**
     * @return \Generator<array{timestamp: string, value: int, value_smooth: int}>
     */
    private function yieldWattsPoints(): \Generator
    {
        foreach ($this->yieldDataPoints(self::MOVING_AVERAGE_PERIOD) as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->getWatts()->watts,
                'value_smooth' => $point->getWattsAverage()->watts,
            ];
        }
    }

    /**
     * @return array<array{timestamp: string, value: int}>
     */
    public function getHeartRatePoints(): array
    {
        return iterator_to_array($this->yieldHeartRatePoints());
    }

    /**
     * @return \Generator<array{timestamp: string, value: int}>
     */
    private function yieldHeartRatePoints(): \Generator
    {
        foreach ($this->yieldDataPoints(self::MOVING_AVERAGE_PERIOD) as $point) {
            yield [
                'timestamp' => $point->getTimestamp()->format('H:i:s'),
                'value' => $point->getHeartRate()->bpm,
            ];
        }
    }

    public function hasVelocityData(): bool
    {
        return !empty($this->getVelocityRawPoints());
    }

    public function hasHeartRateData(): bool
    {
        return !empty($this->getHeartRatePoints());
    }

    public function hasAnyData(): bool
    {
        return $this->hasVelocityData() || $this->hasHeartRateData();
    }

    public function getMovingAveragePeriod(): int
    {
        return self::MOVING_AVERAGE_PERIOD;
    }

    public function getHeartRateAverage(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }
        $sum = array_sum(array_map(fn ($point) => $point['value'], $points));

        return (int) round($sum / count($points));
    }

    public function getHeartRateMax(): int
    {
        $points = $this->getHeartRatePoints();
        if (empty($points)) {
            return 0;
        }

        return max(array_map(fn ($point) => $point['value'], $points));
    }

    public function getVelocityAverage(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $sum = 0;
        $count = 0;
        foreach ($points as $point) {
            $velocity = $point->getVelocityAverage();
            if ($velocity->isZero()) {
                continue;
            }

            $sum += $velocity->metersPerSecond;
            ++$count;
        }
        $average = $count > 0 ? $sum / $count : 0;

        return new Velocity($average);
    }

    public function getVelocityMax(): Velocity
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return new Velocity(0.0);
        }
        $velocities = [];
        foreach ($points as $point) {
            $velocity = $point->getVelocityAverage();
            if (!$velocity->isZero()) {
                $velocities[] = $velocity->metersPerSecond;
            }
        }

        return Velocity::max($velocities);
    }

    public function getPowerAverage(): Power
    {
        $points = $this->getDataPoints();
        if (empty($points)) {
            return Power::ZERO();
        }

        $watts = [];
        foreach ($points as $point) {
            $power = $point->getWatts();
            if (!$power->isZero()) {
                $watts[] = $power->watts;
            }
        }

        return Power::average($watts);
    }

    /**
     * @return array{
     *     activity_id: int,
     *     profile_picture: string,
     *     meta_data: ActivityMetaData,
     *     metrics: TrainingMetrics,
     *     pace_zone_sessions: array<Session>,
     *     heart_rate_zone_sessions: array<Session>,
     *     pace_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     *     hr_stats: array<array{zone: string, duration_seconds: int, duration_percentage: float}>,
     * }
     */
    public function toArray(): array
    {
        $sessions = $this->createZoneSessions();
        $heartRateZoneSessions = $sessions->getHeartRateSessions();
        $paceZoneSessions = $sessions->getPaceZoneSessions();

        $metrics = TrainingMetrics::fromSessions($paceZoneSessions);
        $paceStats = Statistics::fromSessions($this->athlete, $paceZoneSessions, PaceZone::cases());
        $hrStats = Statistics::fromSessions($this->athlete, $heartRateZoneSessions, HeartRateZone::cases());

        // Use zone session totals instead of moving_time
        // This ensures percentages always aggregate to 100%
        $totalPaceSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $paceZoneSessions));
        $totalHrSeconds = array_sum(array_map(fn ($s) => $s->getSeconds(), $heartRateZoneSessions));

        $paceStatsArray = $paceStats->toArray($totalPaceSeconds);
        $hrStatsArray = $hrStats->toArray($totalHrSeconds);

        return [
            'activity_id' => $this->activity->getId(),
            'profile_picture' => $this->athlete->getProfilePicture(),
            'meta_data' => $this->getMetaData(),
            'activity_data' => $this,
            'metrics' => $metrics,
            'pace_zone_sessions' => $paceZoneSessions,
            'pace_stats' => $paceStatsArray,
            'heart_rate_zone_sessions' => $heartRateZoneSessions,
            'hr_stats' => $hrStatsArray,
        ];
    }

    private function convertVelocity(Velocity $velocity): float
    {
        $type = $this->activity->data['type'];

        return match ($type) {
            'Run', 'Walk', 'Hike' => $velocity->asPace()->value,
            'Swim' => $velocity->asPace()->as(PaceUnit::MINUTES_PER_100_METERS)->value,
            'Ride' => $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR)->value,
            default => 0.0,
        };
    }

    private function createZoneSessions(): MomentumActivity
    {
        Assert::that(!$this->athlete->getRunTreshold()->isZero(), 'Please configure a treshold!');
        Assert::that($this->athlete->getMaxHeartRate()->isValid(), 'Please configure a max. heart rate!');

        $startDate = $this->activity->getStartDateLocalTime();
        $dataPoints = iterator_to_array($this->stream->yield($startDate, 50));

        return MomentumActivity::create(
            $this->athlete,
            $this->activity->getEndDateLocalTime(),
            $dataPoints
        );
    }
}
