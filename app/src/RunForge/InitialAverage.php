<?php

declare(strict_types=1);

namespace App\RunForge;

final class InitialAverage extends Average
{
    public function __construct(\DateTimeImmutable $timestamp)
    {
        parent::__construct($timestamp, 0.0);
    }
}
