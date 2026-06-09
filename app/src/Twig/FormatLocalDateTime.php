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

    public static function format(\DateTimeImmutable|\DateTime $dateTime, string $format = 'Y-m-d H:i:s'): string
    {
        if (!$dateTime instanceof \DateTimeImmutable) {
            $dateTime = \DateTimeImmutable::createFromMutable($dateTime);
        }

        return $dateTime->format($format);
    }
}
