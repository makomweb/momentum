<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class GlobalVariables extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly string $version,
    ) {
    }

    public function getGlobals(): array
    {
        return [
            'appVersion' => $this->version,
        ];
    }
}
