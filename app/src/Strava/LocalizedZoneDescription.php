<?php

declare(strict_types=1);

namespace App\Strava;

use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use App\Momentum\ZoneDescription;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LocalizedZoneDescription
{
    public function __construct(
        private ZoneDescription $zoneDescription,
        private TranslatorInterface $translator,
    ) {
    }

    public function getDescription(HeartRateZone|PaceZone $zone): string
    {
        $description = $this->zoneDescription->getDescription($zone);

        // Replace translation keys with actual translations
        if ($zone instanceof HeartRateZone) {
            $key = $this->zoneDescription->getHeartRateZoneNameKey($zone);
        } else {
            $key = $this->zoneDescription->getPaceZoneNameKey($zone);
        }

        $translation = $this->translator->trans($key);

        return str_replace($key, $translation, $description);
    }
}
