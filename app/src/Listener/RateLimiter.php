<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 50)]
final class RateLimiter
{
    public function __construct(
        private RateLimiterFactoryInterface $limiterFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $clientIp = $request->getClientIp() ?? 'unknown';

        $limiter = $this->limiterFactory->create($clientIp);
        $limit = $limiter->consume(1);

        if (!$limit->isAccepted()) {
            $this->logger->warning('Rate limit exceeded', [
                'client_ip' => $clientIp,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'retry_after_timestamp' => $limit->getRetryAfter()->getTimestamp(),
            ]);

            $event->setResponse(new Response(
                'Too Many Requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => '30 seconds']
            ));

            return; // Reject the request.
        }
    }
}
