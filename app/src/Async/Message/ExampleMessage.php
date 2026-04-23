<?php

declare(strict_types=1);

namespace App\Async\Message;

/**
 * Example async message demonstrating how to dispatch async tasks.
 * In practice, replace with domain-specific messages (e.g., SyncActivitiesMessage, ProcessActivityMessage).
 */
final readonly class ExampleMessage
{
    public function __construct(
        public int $userId,
        public string $taskName,
    ) {
    }
}
