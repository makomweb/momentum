<?php

declare(strict_types=1);

namespace App\Twig;

use App\Momentum\HeartRateZone;
use App\Momentum\PaceZone;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatZone extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('zone_color', self::getZoneColor(...)),
            new TwigFilter('zone_label', self::getZoneLabel(...)),
        ];
    }

    public static function getZoneColor(string $value, string $type): string
    {
        return match (true) {
            in_array($type, ['hf', 'hr', 'heart_rate', 'pulse']) => self::getHeartRateZoneColor(HeartRateZone::from($value)),
            in_array($type, ['pace', 'speed', 'velocity']) => self::getPaceZoneColor(PaceZone::from($value)),
            default => throw new \Exception(sprintf('Combination %s (value) and %s (type) not supported!', $value, $type)),
        };
    }

    public static function getHeartRateZoneColor(HeartRateZone $zone): string
    {
        return match ($zone) {
            HeartRateZone::ZONE_1 => '#1e88e5', // Blue - Warm-up
            HeartRateZone::ZONE_2 => '#43a047', // Green - Recovery
            HeartRateZone::ZONE_3 => '#ffa726', // Orange - Aerobic
            HeartRateZone::ZONE_4 => '#e53935', // Red - Threshold
            HeartRateZone::ZONE_5 => '#8e24aa', // Purple - Maximum
        };
    }

    public static function getPaceZoneColor(PaceZone $zone): string
    {
        return match ($zone) {
            PaceZone::ZONE_1 => '#1e88e5', // Blue - Recovery
            PaceZone::ZONE_2 => '#43a047', // Green - Basis
            PaceZone::ZONE_3 => '#ffa726', // Orange - Tempo
            PaceZone::ZONE_4 => '#e53935', // Red - Threshold
            PaceZone::ZONE_5 => '#8e24aa', // Purple - VO2max
        };
    }

    private function getZoneLabel(HeartRateZone|PaceZone $zone): string
    {
        return match (true) {
            ($zone instanceof HeartRateZone) => $this->getHeartRateZoneLabel($zone),
            default => $this->getPaceZoneLabel($zone),
        };
    }

    private function getHeartRateZoneLabel(HeartRateZone $zone): string
    {
        return match ($zone) {
            HeartRateZone::ZONE_1 => $this->translator->trans('activity.hr_zone_1'),
            HeartRateZone::ZONE_2 => $this->translator->trans('activity.hr_zone_2'),
            HeartRateZone::ZONE_3 => $this->translator->trans('activity.hr_zone_3'),
            HeartRateZone::ZONE_4 => $this->translator->trans('activity.hr_zone_4'),
            HeartRateZone::ZONE_5 => $this->translator->trans('activity.hr_zone_5'),
        };
    }

    private function getPaceZoneLabel(PaceZone $zone): string
    {
        return match ($zone) {
            PaceZone::ZONE_1 => $this->translator->trans('activity.pace_zone_1'),
            PaceZone::ZONE_2 => $this->translator->trans('activity.pace_zone_2'),
            PaceZone::ZONE_3 => $this->translator->trans('activity.pace_zone_3'),
            PaceZone::ZONE_4 => $this->translator->trans('activity.pace_zone_4'),
            PaceZone::ZONE_5 => $this->translator->trans('activity.pace_zone_5'),
        };
    }
}
