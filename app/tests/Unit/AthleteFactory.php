<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Momentum\AthleteInterface;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\Power;

final readonly class AthleteFactory
{
    public static function createAthlete(
        Pace $runThreshold,
        Power $cyclingThreshold,
        HeartRate $maxHeartRate,
    ): AthleteInterface {
        return new class($runThreshold, $cyclingThreshold, $maxHeartRate) implements AthleteInterface {
            public function __construct(
                private Pace $runThreshold,
                private Power $cyclingThreshold,
                private HeartRate $maxHeartRate,
            ) {
            }

            public function getId(): int
            {
                return 123;
            }

            public function getName(): string
            {
                throw new \Exception('Not implemented');
            }

            public function getRunTreshold(): Pace
            {
                return $this->runThreshold;
            }

            public function getCyclingTreshold(): Power
            {
                return $this->cyclingThreshold;
            }

            public function getMaxHeartRate(): HeartRate
            {
                return $this->maxHeartRate;
            }

            public function getProfilePicture(): string
            {
                throw new \Exception('Not implemented');
            }

            public function isConfigured(): bool
            {
                throw new \Exception('Not implemented');
            }

            public function toArray(): array
            {
                throw new \Exception('Not implemented');
            }
        };
    }
}
