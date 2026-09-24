<?php

namespace App\Tests\Unit;

use App\Twig\FormatLocalDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FormatDayTest extends TestCase
{
    #[Test]
    #[DataProvider('provideTestData')]
    public function format_day_should_work(\DateTimeImmutable $actual, string $expected): void
    {
        self::assertEquals(FormatLocalDateTime::formatDay($actual), $expected);
    }

    /** @return array<\DateTimeImmutable, string>[] */
    public static function provideTestData(): array
    {
        $today = new \DateTimeImmutable();
        $yesterday = $today->sub(\DateInterval::createFromDateString('1day'));

        return [
            [$today, 'Today'],
            [$yesterday, 'Yesterday'],
            [\DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, '2026-08-23T17:02:31+02:00'), 'Sun, 23 Aug 2026 · 17:02'],
        ];
    }
}
