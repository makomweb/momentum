<?php

declare(strict_types=1);

namespace App\Strava\API;

final readonly class HeartRate implements \Stringable
{
    public function __construct(public int $bpm)
    {
    }

    public function __toString(): string
    {
        return sprintf('%d bpm', $this->bpm);
    }
}
