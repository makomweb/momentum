<?php

namespace App\Tests\Unit;

use App\Twig\FormatLocalDateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormatDayTest extends TestCase
{
    private FormatLocalDateTime $filter;
    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->method('getLocale')->willReturn('en');
        $this->translator->method('trans')
            ->willReturnCallback(fn ($key) => match ($key) {
                'date.today' => 'Today',
                'date.yesterday' => 'Yesterday',
                default => $key,
            });

        $this->filter = new FormatLocalDateTime($this->translator);
    }

    #[Test]
    #[DataProvider('provideTestData')]
    /**
     * @param non-empty-string $prefix
     */
    public function format_day_should_work(\DateTimeImmutable $actual, string $prefix): void
    {
        \assert(!empty($prefix));
        self::assertStringStartsWith($prefix, $this->filter->format($actual));
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
            [$otherDate, 'Sun, 08/23/2026 · 5:02 PM'],
        ];
    }
}
