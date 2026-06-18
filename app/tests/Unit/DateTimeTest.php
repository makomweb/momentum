<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DateTimeTest extends TestCase
{
    #[Test]
    public function format_fetch_after_timestamp(): void
    {
        $dt = \DateTimeImmutable::createFromTimestamp(1774915201);

        self::assertEquals('Tue, 31 Mar 2026 00:00:01 +0000', $dt->format(\DateTimeImmutable::RFC2822));
    }
}
