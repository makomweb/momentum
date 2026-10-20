<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;

#[AsEventListener(event: 'kernel.request', priority: 25)]
final class LocaleListener
{
    private const SUPPORTED_LOCALES = ['en', 'de'];

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // Priority 1: Check for _locale cookie (user preference)
        if ($request->cookies->has('_locale')) {
            $locale = $request->cookies->get('_locale');
            if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
                $request->setLocale($locale);
                return;
            }
        }

        // Priority 2: Check Accept-Language header
        $acceptLanguage = $request->headers->get('Accept-Language');
        if ($acceptLanguage) {
            // Parse Accept-Language header (e.g., "de,de-DE;q=0.9,en;q=0.8")
            $locales = $this->parseAcceptLanguage($acceptLanguage);
            foreach ($locales as $locale) {
                if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
                    $request->setLocale($locale);
                    return;
                }
            }
        }

        // Default to en if nothing matches
        $request->setLocale('en');
    }

    private function parseAcceptLanguage(string $acceptLanguage): array
    {
        $locales = [];
        $parts = explode(',', $acceptLanguage);

        foreach ($parts as $part) {
            $part = trim($part);
            // Extract language code before semicolon (q-value)
            if (strpos($part, ';') !== false) {
                [$locale] = explode(';', $part);
            } else {
                $locale = $part;
            }

            $locale = trim($locale);
            // Convert "de-DE" to "de"
            if (strpos($locale, '-') !== false) {
                [$locale] = explode('-', $locale);
            }

            if (!empty($locale)) {
                $locales[] = strtolower($locale);
            }
        }

        return array_unique($locales);
    }
}
