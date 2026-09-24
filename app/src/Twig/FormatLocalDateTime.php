<?php

declare(strict_types=1);

namespace App\Twig;

use Exception;
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
        throw new Exception('TODO!');
    }
}
