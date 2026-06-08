<?php

declare(strict_types=1);

namespace App\Async\Message;

use App\Momentum\AthleteInterface;

final readonly class SyncActivitiesMessage
{
    public function __construct(public int $stravaUserId)
    {
    }

    public static function for(AthleteInterface $athlete): self
    {
        return new self($athlete->getStravaId());
    }
}
