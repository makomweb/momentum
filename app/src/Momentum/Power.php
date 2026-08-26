<?php

declare(strict_types=1);

namespace App\Momentum;

final readonly class Power implements \Stringable
{
    public function __construct(public int $watts)
    {
    }

    /**
     * @param int[] $watts
     */
    public static function average(array $watts): self
    {
        return new self(empty($watts) ? 0 : (int) (array_sum($watts) / count($watts)));
    }

    public static function ZERO(): self
    {
        return new self(0);
    }

    public function isZero(): bool
    {
        return $this->watts < 1;
    }

    public function __toString(): string
    {
        return sprintf('%d W', $this->watts);
    }
}
