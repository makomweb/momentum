<?php

declare(strict_types=1);

namespace App\Async\Handler;

use App\Async\Message\ExampleMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Example message handler demonstrating async task processing.
 * Handlers are automatically discovered and executed by the worker.
 */
#[AsMessageHandler]
final readonly class ExampleMessageHandler
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ExampleMessage $message): void
    {
        // Simulate async work
        $this->logger->info('Processing async task', [
            'userId' => $message->userId,
            'taskName' => $message->taskName,
        ]);

        // Perform the actual async work here
        // This runs in the background worker process
    }
}
