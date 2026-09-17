<?php

declare(strict_types=1);

namespace App\Momentum;

use App\Momentum\Assert\Assert;

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

    public function isTooHard(
        float $maxHard = 30.0, /* percent */
    ): bool {
        Assert::that(0.0 < $maxHard && $maxHard < 100.0);

        return $this->getHardPercentage() >= $maxHard;
    }

    /**
     * Returns true if training aligns with balanced training principles to minimize gray zone (medium) training.
     * At least easy threshold, not more than hard treshold!
     */
    public function isBalanced(
        float $maxEasy = 45.0 /* percent */,
        float $maxHard = 45.0, /* percent */
    ): bool {
        Assert::that(0.0 < $maxEasy + $maxHard && $maxEasy + $maxHard < 100.0);

        $maxMedium = 100.0 - ($maxEasy + $maxHard);

        /*
        dump(json_encode([
            'max_easy' => $maxEasy,
            'max_medium' => $maxMedium,
            'max_hard' => $maxHard,
            'easy' => $this->getEasyPercentage(),
            'medium' => $this->getMediumPercentage(),
            'hard' => $this->getHardPercentage(),
        ], JSON_PRETTY_PRINT));

        return $easyPercentage >= $easyThreshold
            && $mediumPercentage <= $maxMedium
            && $hardPercentage <= $hardThreshold;
        */

        return $this->getEasyPercentage() >= $maxEasy
            && $this->getMediumPercentage() <= $maxMedium
            && $this->getHardPercentage() <= $maxHard;
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
