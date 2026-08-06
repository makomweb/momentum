<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class ZoneDescription
{
    public function __construct(private AthleteInterface $athlete)
    {
        assert(!$athlete->getTreshold()->isZero(), 'Please configure a treshold!');
        assert($athlete->getMaxHeartRate()->isValid(), 'Please configure a max. heart rate!');
    }

    public function getDescription(HeartRateZone|PaceZone $zone): string
    {
        return $zone->value;

        // if ($zone instanceof HeartRateZone) {
        //     return $this->getHeartRateZoneDescription($zone);
        // }

        // return $this->getPaceZoneDescription($zone);
    }

    private function getHeartRateZoneDescription(HeartRateZone $zone): string
    {
        return match ($zone) {
            HeartRateZone::ZONE_5 => sprintf('%s >= %s', $zone->value, $this->athlete->getMaxHeartRate()->multiply(0.9)),
            HeartRateZone::ZONE_4 => sprintf('%s >= %s', $zone->value, $this->athlete->getMaxHeartRate()->multiply(0.8)),
            HeartRateZone::ZONE_3 => sprintf('%s >= %s', $zone->value, $this->athlete->getMaxHeartRate()->multiply(0.7)),
            HeartRateZone::ZONE_2 => sprintf('%s >= %s', $zone->value, $this->athlete->getMaxHeartRate()->multiply(0.6)),
            default => $zone->value,
        };
    }

    private function getPaceZoneDescription(PaceZone $zone): string
    {
        return match ($zone) {
            PaceZone::ZONE_1 => sprintf('%s <= %s', $zone->value, $this->athlete->getTreshold()->multiply(1.29)),
            PaceZone::ZONE_2 => sprintf('%s <= %s', $zone->value, $this->athlete->getTreshold()->multiply(1.14)),
            PaceZone::ZONE_3 => sprintf('%s <= %s', $zone->value, $this->athlete->getTreshold()->multiply(1.06)),
            PaceZone::ZONE_4 => sprintf('%s <= %s', $zone->value, $this->athlete->getTreshold()->multiply(0.99)),
            default => $zone->value,
        };
    }
}
