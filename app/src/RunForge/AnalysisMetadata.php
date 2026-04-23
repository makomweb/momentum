<?php

declare(strict_types=1);

namespace App\RunForge;

use App\Strava\API\Speed;

final readonly class AnalysisMetadata
{
    public function __construct(
        public string $metricLabel,           // "Pace" or "Speed"
        public string $chartTitle,            // "Pace Over Time" or "Speed Over Time"
        public string $chartYAxisLabel,       // "min/km", "km/h", "m/s"
        public string $emoji,                 // 🏃, 🚴, etc.
        public Speed $averageSpeed,
        public bool $usesInverseScale,        // true for pace (lower = faster), false for speed
    ) {
    }

    public static function fromActivityType(string $type, Speed $averageSpeed): self
    {
        return match ($type) {
            'Run' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🏃',
                averageSpeed: $averageSpeed,
                usesInverseScale: true,
            ),
            'Walk' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🚶',
                averageSpeed: $averageSpeed,
                usesInverseScale: true,
            ),
            'Hike' => new self(
                metricLabel: 'Pace',
                chartTitle: '⏱️ Pace Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🥾',
                averageSpeed: $averageSpeed,
                usesInverseScale: true,
            ),
            'Ride' => new self(
                metricLabel: 'Speed',
                chartTitle: '⚡ Speed Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🚴',
                averageSpeed: $averageSpeed,
                usesInverseScale: false,
            ),
            'Swim' => new self(
                metricLabel: 'Speed',
                chartTitle: '🌊 Speed Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🏊',
                averageSpeed: $averageSpeed,
                usesInverseScale: false,
            ),
            default => new self(
                metricLabel: 'Speed',
                chartTitle: '⚡ Speed Over Time',
                chartYAxisLabel: $averageSpeed->unit->value,
                emoji: '🏅',
                averageSpeed: $averageSpeed,
                usesInverseScale: false,
            ),
        };
    }
}
