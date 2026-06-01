<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class TrainingMetrics
{
    private function __construct(
        public int $easySeconds,
        public int $mediumSeconds,
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
            new self(0, 0, 0, $totalSeconds)
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

    public function getMediumSeconds(): int
    {
        return $this->mediumSeconds;
    }

    public function getMediumPercentage(): float
    {
        return $this->totalSeconds > 0 ? ($this->mediumSeconds / $this->totalSeconds) * 100 : 0.0;
    }

    /**
     * Returns true if training aligns with balanced training principles to minimize gray zone (medium) training.
     * At least easy threshold, not more than hard treshold!
     */
    public function isBalanced(
        float $easyThreshold = 45.0 /* percent */,
        float $hardThreshold = 45.0, /* percent */
    ): bool {
        $maxMedium = 100.0 - ($easyThreshold + $hardThreshold);

        $easyPercentage = $this->getEasyPercentage();
        $mediumPercentage = $this->getMediumPercentage();
        $hardPercentage = $this->getHardPercentage();

        return $easyPercentage >= $easyThreshold
            && $mediumPercentage <= $maxMedium
            && $hardPercentage <= $hardThreshold;
    }

    private function with(ZoneSession $session): self
    {
        if ($session->isEasy()) {
            return new self(
                $this->easySeconds + $session->getSeconds(),
                $this->mediumSeconds,
                $this->hardSeconds,
                $this->totalSeconds
            );
        }

        if ($session->isHard()) {
            return new self(
                $this->easySeconds,
                $this->mediumSeconds,
                $this->hardSeconds + $session->getSeconds(),
                $this->totalSeconds
            );
        }

        return new self(
            $this->easySeconds,
            $this->mediumSeconds + $session->getSeconds(),
            $this->hardSeconds,
            $this->totalSeconds
        );
    }
}
