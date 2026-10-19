<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatMovingTime extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_moving_time', self::format(...)),
        ];
    }

    public static function format(\DateInterval $dateInterval): string
    {
        $totalSeconds = (int) $dateInterval->s;

        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            if ($minutes > 0) {
                return sprintf('%d h %d min %d s', $hours, $minutes, $seconds);
            }

            return sprintf('%d h %d s', $hours, $seconds);
        }

        if ($minutes > 0) {
            return sprintf('%d min %d s', $minutes, $seconds);
        }

        return sprintf('%d s', $seconds);
    }
}
