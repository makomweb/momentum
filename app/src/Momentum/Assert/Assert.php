<?php

declare(strict_types=1);

namespace App\Momentum\Assert;

final readonly class Assert
{
    public static function that(bool $condition, string|\Throwable|null $error = null): void
    {
        if (!$condition) {
            if (is_string($error)) {
                throw new AssertException($error);
            }

            if ($error instanceof \Throwable) {
                throw $error;
            }

            throw new AssertException('Assertion failed!');
        }
    }
}
