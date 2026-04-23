<?php

declare(strict_types=1);

namespace App\Async\Message;

final readonly class SyncActivitiesMessage
{
    public function __construct(public int $stravaUserId) {}
}
