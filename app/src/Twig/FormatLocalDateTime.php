<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class FormatLocalDateTime extends AbstractExtension
{
    public function __construct(private readonly TranslatorInterface $translator) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('format_local_date_time', $this->format(...)),
        ];
    }

    public function format(\DateTimeInterface $dateTime, ?string $template = null): string
    {
        $locale = $this->translator->getLocale();
        
        if ($template === null) {
            $template = match ($locale) {
                'de' => 'D, d.m.Y · H:i',
                default => 'D, m/d/Y · H:i',
            };
        }

        $today = new \DateTimeImmutable();
        $yesterday = $today->modify('-1 day');

        if ($dateTime->format('Y-m-d') === $today->format('Y-m-d')) {
            $todayLabel = $this->translator->trans('date.today');
            return sprintf('%s · %s', $todayLabel, $dateTime->format('H:i'));
        }

        if ($dateTime->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            $yesterdayLabel = $this->translator->trans('date.yesterday');
            return sprintf('%s · %s', $yesterdayLabel, $dateTime->format('H:i'));
        }

        return $this->formatLocalizedDate($dateTime, $template, $locale);
    }

    private function formatLocalizedDate(\DateTimeInterface $dateTime, string $template, string $locale): string
    {
        $formatted = $dateTime->format($template);
        
        $dayNames = match ($locale) {
            'de' => ['Mon' => 'Mo', 'Tue' => 'Di', 'Wed' => 'Mi', 'Thu' => 'Do', 'Fri' => 'Fr', 'Sat' => 'Sa', 'Sun' => 'So'],
            default => ['Mon' => 'Mon', 'Tue' => 'Tue', 'Wed' => 'Wed', 'Thu' => 'Thu', 'Fri' => 'Fri', 'Sat' => 'Sat', 'Sun' => 'Sun'],
        };

        $monthNames = match ($locale) {
            'de' => ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mär', 'Apr' => 'Apr', 'May' => 'Mai', 'Jun' => 'Jun',
                     'Jul' => 'Jul', 'Aug' => 'Aug', 'Sep' => 'Sep', 'Oct' => 'Okt', 'Nov' => 'Nov', 'Dec' => 'Dez'],
            default => ['Jan' => 'Jan', 'Feb' => 'Feb', 'Mar' => 'Mar', 'Apr' => 'Apr', 'May' => 'May', 'Jun' => 'Jun',
                        'Jul' => 'Jul', 'Aug' => 'Aug', 'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Dec'],
        };

        foreach ($dayNames as $en => $localized) {
            $formatted = str_replace($en, $localized, $formatted);
        }

        foreach ($monthNames as $en => $localized) {
            $formatted = str_replace($en, $localized, $formatted);
        }

        return $formatted;
    }
}
