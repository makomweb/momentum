<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class Assert
{
    public static function that(bool $condition, ?string $error = null): void
    {
        if (!$condition) {
            if (is_string($error)) {
                throw new AssertException($error);
            }

            throw new AssertException('Assertion failed!');
        }
    }
}
