<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Twig\FormatLocalDateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AllowMockObjectsWithoutExpectations]
class FormatLocalDateTimeTest extends TestCase
{
    private FormatLocalDateTime $filter;
    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->filter = new FormatLocalDateTime($this->translator);
    }

    public function test_formats_today_in_german(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('de');

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('date.today')
            ->willReturn('Heute');

        $today = new \DateTimeImmutable();
        $result = $this->filter->format($today);

        $this->assertStringContainsString('Heute', $result);
        $this->assertStringContainsString(':', $result);
    }

    public function test_formats_yesterday_in_german(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('de');

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('date.yesterday')
            ->willReturn('Gestern');

        $yesterday = (new \DateTimeImmutable())->modify('-1 day');
        $result = $this->filter->format($yesterday);

        $this->assertStringContainsString('Gestern', $result);
    }

    public function test_formats_past_date_in_german(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('de');

        $pastDate = new \DateTimeImmutable('2026-10-01 14:30:00');
        $result = $this->filter->format($pastDate);

        // German date format should be D, d.m.Y · H:i
        $this->assertStringContainsString('.', $result);
        $this->assertStringContainsString('14:30', $result);
        // Check for German day abbreviation (e.g., Mi for Mittwoch)
        $this->assertMatchesRegularExpression('/^(Mo|Di|Mi|Do|Fr|Sa|So)/', $result);
    }

    public function test_formats_today_in_english(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('date.today')
            ->willReturn('Today');

        $today = new \DateTimeImmutable();
        $result = $this->filter->format($today);

        $this->assertStringContainsString('Today', $result);
        $this->assertStringContainsString(':', $result);
    }

    public function test_formats_past_date_in_english(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $pastDate = new \DateTimeImmutable('2026-10-01 14:30:00');
        $result = $this->filter->format($pastDate);

        // English date format should be D, m/d/Y · g:i A (12-hour format with AM/PM)
        $this->assertStringContainsString('/', $result);
        $this->assertStringContainsString('PM', $result);
        // Check for English day abbreviation
        $this->assertMatchesRegularExpression('/^(Mon|Tue|Wed|Thu|Fri|Sat|Sun)/', $result);
    }

    public function test_german_date_format_uses_dot_as_separator(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('de');

        $testDate = new \DateTimeImmutable('2026-10-15 10:45:00');
        $result = $this->filter->format($testDate);

        // German format: d.m.Y (day.month.year)
        $this->assertMatchesRegularExpression('/\d+\.\d+\.\d+/', $result);
    }

    public function test_english_date_format_uses_slash_as_separator(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $testDate = new \DateTimeImmutable('2026-10-15 10:45:00');
        $result = $this->filter->format($testDate);

        // English format: m/d/Y (month/day/year) with 12-hour time
        $this->assertMatchesRegularExpression('/\d+\/\d+\/\d+/', $result);
        $this->assertMatchesRegularExpression('/\d+:\d+\s(AM|PM)/', $result);
    }

    public function test_english_morning_time_uses_am(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $morningDate = new \DateTimeImmutable('2026-10-15 08:30:00');
        $result = $this->filter->format($morningDate);

        $this->assertStringContainsString('AM', $result);
    }

    public function test_english_afternoon_time_uses_pm(): void
    {
        $this->translator
            ->expects(self::once())
            ->method('getLocale')
            ->willReturn('en');

        $afternoonDate = new \DateTimeImmutable('2026-10-15 14:30:00');
        $result = $this->filter->format($afternoonDate);

        $this->assertStringContainsString('PM', $result);
    }
}
