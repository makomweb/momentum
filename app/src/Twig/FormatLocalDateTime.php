<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatLocalDateTime extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_local_date_time', self::format(...)),
        ];
    }

    public static function format(\DateTimeInterface $dateTime, string $template = 'D, d M Y · H:i'): string
    {
        $today = new \DateTimeImmutable();
        $yesterday = $today->modify('-1 day');

        if ($dateTime->format('Y-m-d') === $today->format('Y-m-d')) {
            return sprintf('Today · %s', $dateTime->format('H:i'));
        }

        if ($dateTime->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            return sprintf('Yesterday · %s', $dateTime->format('H:i'));
        }

        return $dateTime->format($template);
    }
}
