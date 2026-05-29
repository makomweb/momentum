<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class TrainingMetrics
{
    private function __construct(
        public int $easySeconds,
        public int $hardSeconds,
        public int $totalSeconds,
    ) {
    }

    /**
     * @param ZoneSession[] $zoneSessions
     */
    public static function fromSessions(array $zoneSessions, int $totalSeconds): self
    {
        return array_reduce(
            $zoneSessions,
            static fn (self $metrics, ZoneSession $session) => $metrics->with($session),
            new self(0, 0, $totalSeconds)
        );
    }

    public function getEasyPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->easySeconds / $this->totalSeconds) * 100 : 0.0;
    }

    public function getHardPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->hardSeconds / $this->totalSeconds) * 100 : 0.0;
    }

    public function getTempoSeconds(): int
    {
        return $this->totalSeconds - $this->easySeconds - $this->hardSeconds;
    }

    public function getTempoPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->getTempoSeconds() / $this->totalSeconds) * 100 : 0.0;
    }

    /**
     * Returns true if training aligns with balanced training principles to minimize gray zone training.
     * At least easy threshold, not more than hard treshold!
     */
    public function isBalanced(
        float $easyThreshold = 45.0 /* percent */,
        float $hardThreshold = 45.0, /* percent */
    ): bool {
        $maxTempo = 100.0 - ($easyThreshold + $hardThreshold);

        $easyPercentage = $this->getEasyPercentage();
        $TempoPercentage = $this->getTempoPercentage();
        $hardPercentage = $this->getHardPercentage();

        return $easyPercentage >= $easyThreshold
            && $TempoPercentage <= $maxTempo
            && $hardPercentage <= $hardThreshold;
    }

    private function with(ZoneSession $session): self
    {
        if ($session->isEasy()) {
            return new self(
                $this->easySeconds + $session->getSeconds(),
                $this->hardSeconds,
                $this->totalSeconds
            );
        } elseif ($session->isHard()) {
            return new self(
                $this->easySeconds,
                $this->hardSeconds + $session->getSeconds(),
                $this->totalSeconds
            );
        }

        return $this;
    }
}
