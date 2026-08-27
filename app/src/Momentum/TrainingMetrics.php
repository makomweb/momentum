<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class TrainingMetrics
{
    private function __construct(
        public int $easySeconds = 0,
        public int $mediumSeconds = 0,
        public int $hardSeconds = 0,
    ) {
    }

    /**
     * @param Session[] $zoneSessions
     */
    public static function fromSessions(array $zoneSessions): self
    {
        return array_reduce(
            $zoneSessions,
            static fn (self $metrics, Session $session) => $metrics->with($session),
            new self()
        );
    }

    public function getEasyPercentage(): float
    {
        return $this->getTotalSeconds() > 0 ? $this->easySeconds / $this->getTotalSeconds() * 100 : 0.0;
    }

    public function getHardPercentage(): float
    {
        return $this->getTotalSeconds() > 0 ? $this->hardSeconds / $this->getTotalSeconds() * 100 : 0.0;
    }

    public function getMediumPercentage(): float
    {
        return $this->getTotalSeconds() > 0 ? $this->mediumSeconds / $this->getTotalSeconds() * 100 : 0.0;
    }

    private function getTotalSeconds(): int
    {
        return $this->easySeconds + $this->mediumSeconds + $this->hardSeconds;
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

    private function with(Session $session): self
    {
        if ($session->isHard()) {
            return new self(
                $this->easySeconds,
                $this->mediumSeconds,
                $this->hardSeconds + $session->getSeconds()
            );
        }

        if ($session->isEasy()) {
            return new self(
                $this->easySeconds + $session->getSeconds(),
                $this->mediumSeconds,
                $this->hardSeconds
            );
        }

        return new self(
            $this->easySeconds,
            $this->mediumSeconds + $session->getSeconds(),
            $this->hardSeconds
        );
    }
}
