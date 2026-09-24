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

    public static function format(\DateTimeInterface $dateTime, string $format = 'D, d M Y · H:i'): string
    {
        return $dateTime->format($format);
    }

    public static function formatDay(\DateTimeImmutable $dateTime): string
    {
        $today = new \DateTimeImmutable();
        $yesterday = $today->modify('-1 day');

        if ($dateTime->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Today';
        }

        if ($dateTime->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            return 'Yesterday';
        }

        return $dateTime->format('D, d M Y · H:i');
    }
}
