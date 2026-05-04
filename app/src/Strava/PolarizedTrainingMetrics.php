<?php

declare(strict_types=1);

namespace App\Strava;

final readonly class PolarizedTrainingMetrics
{
    public function __construct(
        public int $easySeconds,
        public int $hardSeconds,
        public int $totalSeconds,
    ) {
    }

    public function getEasyPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->easySeconds / $this->totalSeconds) * 100 : 0.0;
    }

    public function getHardPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->hardSeconds / $this->totalSeconds) * 100 : 0.0;
    }

    public function getTempoPercentage(): float
    {
        $tempoSeconds = $this->totalSeconds - $this->easySeconds - $this->hardSeconds;

        return $this->totalSeconds > 0 ? ($tempoSeconds / $this->totalSeconds) * 100 : 0.0;
    }

    /**
     * Returns true if training aligns with polarized principle (80% easy / 20% hard).
     */
    public function isPolarized(
        float $easyThreshold = 75.0,
        float $hardThreshold = 15.0,
    ): bool {
        return $this->getEasyPercentage() >= $easyThreshold
            && $this->getHardPercentage() >= $hardThreshold;
    }
}
