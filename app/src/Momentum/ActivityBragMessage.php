<?php

declare(strict_types=1);

namespace App\Momentum;

/**
 * Generates encouraging, contextual messages for running activities based on performance metrics.
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

        // Check for quick runs (less than 2 minutes)
        if ($movingTime < 120) {
            return sprintf('Quick dash – %s!', $name);
        }

        // Generate message based on type
        return match ($type) {
            'Run', 'Walk', 'Hike' => $this->generate_run_message($distance, $movingTime, $averageSpeed, $name, $userAverageDistance),
            'Ride' => $this->generate_ride_message($distance, $movingTime, $averageSpeed, $name),
            'Swim' => $this->generate_swim_message($distance, $movingTime, $averageSpeed, $name),
            default => sprintf('Great %s – %s!', strtolower($type), $name),
        };
    }

    private function generate_run_message(float $distance, int $movingTime, float $averageSpeed, string $name, ?float $userAverageDistance): string
    {
        // Check distance-based achievements first (prioritize over pace)
        if (null !== $userAverageDistance && $distance > $userAverageDistance * 1.5) {
            return sprintf('🚀 Long run for you – %s was epic!', $name);
        }

        if ($distance > 10) {
            return sprintf('🏃 Great distance on %s!', $name);
        }

        // Then check pace-based achievements
        $velocity = new Velocity($averageSpeed);
        $pace = $velocity->asPace();
        $paceValue = $pace->value;

        // Very fast pace (< 4:30 min/km)
        if ($paceValue < 4.5) {
            return sprintf('🔥 Blazing pace on %s – crushed it!', $name);
        }

        // Strong pace (4:30 - 5:30)
        if ($paceValue < 5.5) {
            return sprintf('💪 Strong effort on %s!', $name);
        }

        // Steady pace (5:30 - 7:00)
        if ($paceValue < 7.0) {
            return sprintf('✨ Solid run – %s was great!', $name);
        }

        // Default encouraging message
        return sprintf('✓ Nice run – %s!', $name);
    }

    private function generate_ride_message(float $distance, int $movingTime, float $averageSpeed, string $name): string
    {
        // Check distance first (prioritize over speed)
        if ($distance > 50) {
            return sprintf('🚴 Epic distance on %s!', $name);
        }

        $velocity = new Velocity($averageSpeed);
        $speed = $velocity->asSpeed()->as(SpeedUnit::KILOMETERS_PER_HOUR);

        // Fast ride (> 29 km/h)
        if ($speed->value > 29) {
            return sprintf('🚴 Fast ride – %s was speedy!', $name);
        }

        // Standard encouraging message
        return sprintf('✨ Great ride – %s!', $name);
    }

    private function generate_swim_message(float $distance, int $movingTime, float $averageSpeed, string $name): string
    {
        // Long swim (> 2000m)
        if ($distance > 2000) {
            return sprintf('🏊 Impressive distance – %s was great!', $name);
        }

        return sprintf('💧 Strong swim – %s!', $name);
    }
}
