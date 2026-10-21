<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Symfony\Contracts\Translation\TranslatorInterface;

final class TranslatorStub implements TranslatorInterface
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        return $id;
    }

    public function getLocale(): string
    {
        return 'en';
    }
}
