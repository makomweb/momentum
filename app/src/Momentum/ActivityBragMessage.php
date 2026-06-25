<?php

declare(strict_types=1);

namespace App\Momentum;

/**
 * Generates encouraging, contextual messages for activities based on performance metrics.
 * Pure domain logic, no framework dependencies.
 */
final class ActivityBragMessage
{
    /**
     * @param array{
     *  distance: float,
     *  moving_time: int,
     *  average_speed: float,
     *  max_speed: float,
     *  type: string,
     *  name?: string
     * } $activityData
     */
    public function generate(array $activityData, ?float $userAverageDistance = null): string
    {
        $type = $activityData['type'];
        $distance = $activityData['distance'];
        $movingTime = $activityData['moving_time'];
        $averageSpeed = $activityData['average_speed'];
        $name = $activityData['name'] ?? 'Activity';

        if ($movingTime < 120) {
            return sprintf('Quick dash – %s!', $name);
        }

        return match ($type) {
            'Run', 'Walk', 'Hike' => self::generate_run_message($distance, $averageSpeed, $userAverageDistance),
            'Ride' => self::generate_ride_message($distance, $averageSpeed),
            'Swim' => self::generate_swim_message($distance),
            default => sprintf('Great %s – %s!', strtolower($type), $name),
        };
    }

    private static function generate_run_message(float $distance, float $averageSpeed, ?float $userAverageDistance): string
    {
        // Check distance-based achievements first (prioritize over pace)
        if (null !== $userAverageDistance && $distance > $userAverageDistance * 1.5) {
            return '🚀 That was epic!';
        }

        if ($distance > 10) {
            return '🏃 Great distance!';
        }

        // Then check pace-based achievements
        $velocity = new Velocity($averageSpeed);
        $pace = $velocity->asPace();
        $paceValue = $pace->value;

        // Very fast pace (< 4:30 min/km)
        if ($paceValue < 4.5) {
            return '🔥 Blazing fast!';
        }

        // Strong pace (4:30 - 5:30)
        if ($paceValue < 5.5) {
            return '💪 Strong effort!';
        }

        // Steady pace (5:30 - 7:00)
        if ($paceValue < 7.0) {
            return '✨ Solid run!';
        }

        // Default encouraging message
        return '✓ Nice run!';
    }

    private static function generate_ride_message(float $distance, float $averageSpeed): string
    {
        // Check distance first (prioritize over speed)
        if ($distance > 50) {
            return '🚴 Epic distance!';
        }

        $velocity = new Velocity($averageSpeed);
        $speed = $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR);

        // Fast ride (> 29 km/h)
        if ($speed->value > 29) {
            return '🚴 That was speedy!';
        }

        // Standard encouraging message
        return '✨ Great ride!';
    }

    private static function generate_swim_message(float $distance): string
    {
        // Long swim (> 2000m)
        if ($distance > 2000) {
            return '🏊 Impressive distance!';
        }

        // Standard encouraging message
        return '💧 Strong swim!';
    }
}
