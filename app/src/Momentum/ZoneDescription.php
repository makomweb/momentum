<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneDescription
{
    public function __construct(private AthleteInterface $athlete)
    {
        assert(!$athlete->getRunTreshold()->isZero(), 'Please configure a treshold!');
        assert($athlete->getMaxHeartRate()->isValid(), 'Please configure a max. heart rate!');
    }

    public function getDescription(HeartRateZone|PaceZone $zone): string
    {
        if ($zone instanceof HeartRateZone) {
            return $this->getHeartRateZoneDescription($zone);
        }

        return $this->getPaceZoneDescription($zone);
    }

    /**
     * Get the translation key for a heart rate zone name.
     */
    public function getHeartRateZoneNameKey(HeartRateZone $zone): string
    {
        return match ($zone) {
            HeartRateZone::ZONE_1 => 'activity.hr_zone_1',
            HeartRateZone::ZONE_2 => 'activity.hr_zone_2',
            HeartRateZone::ZONE_3 => 'activity.hr_zone_3',
            HeartRateZone::ZONE_4 => 'activity.hr_zone_4',
            HeartRateZone::ZONE_5 => 'activity.hr_zone_5',
        };
    }

    /**
     * Get the translation key for a pace zone name.
     */
    public function getPaceZoneNameKey(PaceZone $zone): string
    {
        return match ($zone) {
            PaceZone::ZONE_1 => 'activity.pace_zone_1',
            PaceZone::ZONE_2 => 'activity.pace_zone_2',
            PaceZone::ZONE_3 => 'activity.pace_zone_3',
            PaceZone::ZONE_4 => 'activity.pace_zone_4',
            PaceZone::ZONE_5 => 'activity.pace_zone_5',
        };
    }

    private function getHeartRateZoneDescription(HeartRateZone $zone): string
    {
        $threshold = HeartRateZone::ZONE_1 === $zone ? '<' : '≥';

        return match ($zone) {
            HeartRateZone::ZONE_1 => sprintf('%s (%s %d)', $this->getHeartRateZoneNameKey($zone), $threshold, $this->athlete->getMaxHeartRate()->multiply(0.6)->bpm),
            HeartRateZone::ZONE_2 => sprintf('%s (≥ %d)', $this->getHeartRateZoneNameKey($zone), $this->athlete->getMaxHeartRate()->multiply(0.6)->bpm),
            HeartRateZone::ZONE_3 => sprintf('%s (≥ %d)', $this->getHeartRateZoneNameKey($zone), $this->athlete->getMaxHeartRate()->multiply(0.7)->bpm),
            HeartRateZone::ZONE_4 => sprintf('%s (≥ %d)', $this->getHeartRateZoneNameKey($zone), $this->athlete->getMaxHeartRate()->multiply(0.8)->bpm),
            default => sprintf('%s (≥ %d)', $this->getHeartRateZoneNameKey($zone), $this->athlete->getMaxHeartRate()->multiply(0.9)->bpm),
        };
    }

    private function getPaceZoneDescription(PaceZone $zone): string
    {
        return match ($zone) {
            PaceZone::ZONE_1 => sprintf('%s (≤ %s)', $this->getPaceZoneNameKey($zone), (string) $this->athlete->getRunTreshold()->multiply(1.29)),
            PaceZone::ZONE_2 => sprintf('%s (≤ %s)', $this->getPaceZoneNameKey($zone), (string) $this->athlete->getRunTreshold()->multiply(1.14)),
            PaceZone::ZONE_3 => sprintf('%s (≤ %s)', $this->getPaceZoneNameKey($zone), (string) $this->athlete->getRunTreshold()->multiply(1.06)),
            PaceZone::ZONE_4 => sprintf('%s (≤ %s)', $this->getPaceZoneNameKey($zone), (string) $this->athlete->getRunTreshold()->multiply(0.99)),
            default => sprintf('%s (> %s)', $this->getPaceZoneNameKey($zone), (string) $this->athlete->getRunTreshold()->multiply(0.99)),
        };
    }
}
