<?php

declare(strict_types=1);

namespace App\Momentum;

/**
 * Validates that pace zone and heart rate zone align appropriately.
 * Hard zones (4-5) require confirming high heart rate.
 * Easy zones (1-2) should not have mismatches.
 */
final class ZoneValidator
{
    /**
     * Check if pace zone is confirmed by heart rate zone.
     * Hard pace zones should have corresponding hard HR zones.
     * Easy pace zones should not have extreme HR mismatches.
     */
    public static function isPaceZoneConfirmed(
        PaceZone $paceZone,
        HeartRateZone $hrZone,
    ): bool {
        return match (true) {
            // Hard pace zones (Threshold, VO2max) need hard HR zones (Threshold, Maximum)
            PaceZone::ZONE_4 === $paceZone || PaceZone::ZONE_5 === $paceZone => (
                HeartRateZone::ZONE_4 === $hrZone || HeartRateZone::ZONE_5 === $hrZone
            ),
            // Tempo pace zone accepts HR zones 3-4 (Aerobic/Moderate to Threshold/Hard)
            PaceZone::ZONE_3 === $paceZone => (
                HeartRateZone::ZONE_3 === $hrZone || HeartRateZone::ZONE_4 === $hrZone
            ),
            // Easy/Recovery pace zones are always valid
            default => true,
        };
    }
}
