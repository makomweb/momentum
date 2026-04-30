<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class HeartFrequency implements \Stringable
{
    public function __construct(public int $value)
    {
    }

    public function __toString(): string
    {
        return sprintf('%d bpm', $this->value);
    }
}
