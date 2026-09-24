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
    /**
     * @param non-empty-string $prefix
     */
    public function format_day_should_work(\DateTimeImmutable $actual, string $prefix): void
    {
        \assert(!empty($prefix));
        self::assertStringStartsWith($prefix, FormatLocalDateTime::format($actual));
    }

    /** @return array<array{0: \DateTimeImmutable, 1: non-empty-string}> */
    public static function provideTestData(): array
    {
        $today = new \DateTimeImmutable();
        $yesterday = $today->sub(\DateInterval::createFromDateString('1day'));
        $otherDate = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, '2026-08-23T17:02:31+02:00');
        \assert($otherDate instanceof \DateTimeImmutable);

        return [
            [$today, 'Today · '],
            [$yesterday, 'Yesterday · '],
            [$otherDate, 'Sun, 23 Aug 2026 · 17:02'],
        ];
    }
}
