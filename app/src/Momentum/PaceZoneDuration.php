<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class PaceZoneDuration
{
    public function __construct(
        public PaceZone $zone,
        public int $seconds,
        public int $totalSeconds,
    ) {
    }

    public function add(ZoneSession $session): self
    {
        if ($session->isEqual($this->zone)) {
            return new self($this->zone, $this->seconds + $session->durationSeconds, $this->totalSeconds);
        }

        return $this;
    }

    public function getDurationPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->seconds / $this->totalSeconds) * 100 : 0.0;
    }

    /**
     * @return array{
     *  zone: string,
     *  duration_seconds: int,
     *  duration_percentage: float
     * }
     */
    public function toArray(): array
    {
        return [
            'zone' => $this->zone->value,
            'duration_seconds' => $this->seconds,
            'duration_percentage' => round($this->getDurationPercentage(), 2),
        ];
    }
}
