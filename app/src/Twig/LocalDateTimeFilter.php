<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class LocalDateTimeFilter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('local_date', [$this, 'formatLocalDate']),
        ];
    }

    public function formatLocalDate(\DateTimeImmutable|\DateTime $dateTime, string $format = 'Y-m-d H:i:s'): string
    {
        if (!$dateTime instanceof \DateTimeImmutable) {
            $dateTime = \DateTimeImmutable::createFromMutable($dateTime);
        }

        return $dateTime->format($format);
    }
}
