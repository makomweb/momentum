<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

#[AsEventListener(event: ResponseEvent::class)]
final readonly class ResponseLogger
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        $this->logger->info(sprintf(
            '%s %s %d %s',
            $request->getMethod(),
            $request->getPathInfo(),
            $response->getStatusCode(),
            self::getMessage($response)
        ));
    }

    private static function getMessage(Response $response): string
    {
        return match (true) {
            $response->isSuccessful() => 'success',
            $response->isClientError() => 'client_error',
            $response->isServerError() => 'server_error',
            $response->isRedirect() => 'redirect',
            // default => throw new \InvalidArgumentException(sprintf('Response %d is not supported!', $response->getStatusCode())),
            default => 'unqualified',
        };
    }
}
