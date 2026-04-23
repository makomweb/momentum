<?php

declare(strict_types=1);

namespace App\Strava;

use App\Strava\API\Velocity;

final readonly class AnalysisMetadata
{
    public function __construct(
        public string $metricLabel,           // "Pace" or "Speed"
        public string $chartTitle,            // "Pace Over Time" or "Speed Over Time"
        public string $chartYAxisLabel,       // "min/km", "km/h", "m/s"
        public string $emoji,                 // 🏃, 🚴, etc.
        public Velocity $averageVelocity,
        public bool $usesInverseScale,        // true for pace (lower = faster), false for speed
    ) {
    }

    public static function fromActivityType(string $type, Velocity $averageVelocity): self
    {
        return match ($type) {
            'Run' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🏃',
                averageVelocity: $averageVelocity,
                usesInverseScale: true,
            ),
            'Walk' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🚶',
                averageVelocity: $averageVelocity,
                usesInverseScale: true,
            ),
            'Hike' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🥾',
                averageVelocity: $averageVelocity,
                usesInverseScale: true,
            ),
            'Ride' => new self(
                metricLabel: 'Speed',
                chartTitle: '⚡ Speed Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🚴',
                averageVelocity: $averageVelocity,
                usesInverseScale: false,
            ),
            'Swim' => new self(
                metricLabel: 'Speed',
                chartTitle: '🌊 Speed Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🏊',
                averageVelocity: $averageVelocity,
                usesInverseScale: false,
            ),
            default => new self(
                metricLabel: 'Speed',
                chartTitle: '⚡ Speed Over Time',
                chartYAxisLabel: $averageVelocity->unit->value,
                emoji: '🏅',
                averageVelocity: $averageVelocity,
                usesInverseScale: false,
            ),
        };
    }
}
