<?php

declare(strict_types=1);

namespace App\Twig;

use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatZoneColor extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_zone_color', self::format(...)),
        ];
    }

    private static function format(string $value, string $type): string
    {
        $zone = match (true) {
            in_array($type, ['hr', 'heart_rate', 'pulse']) => HeartRateZone::from($value),
            in_array($type, ['pace', 'speed', 'velocity']) => PaceZone::from($value),
            default => throw new \Exception(sprintf('Combination %s (value) and %s (type) not supported!', $value, $type)),
        };

        return $zone->getColor();
    }
}
