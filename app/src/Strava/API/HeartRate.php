<?php

declare(strict_types=1);

namespace App\Strava\API;

use App\Momentum\HeartRateZone;

final readonly class HeartRate implements \Stringable
{
    public function __construct(public int $bpm = 0)
    {
    }

    public function isValid(): bool
    {
        return $this->bpm > 0;
    }

    public function getZone(HeartRate $max): HeartRateZone
    {
        return HeartRateZone::calculate($this->bpm, $max->bpm);
    }

    public function __toString(): string
    {
        return sprintf('%d bpm', $this->bpm);
    }
}
