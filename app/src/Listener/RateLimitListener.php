<?php

declare(strict_types=1);

namespace App\Listener;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 50)]
final class RateLimitListener
{
    public function __construct(
        private RateLimiterFactory $perIpRateLimiter,
        private RateLimiterFactory $globalRateLimiter,
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

        // Check per-IP rate limit
        $perIpLimiter = $this->perIpRateLimiter->create($clientIp);
        $perIpLimit = $perIpLimiter->consume(1);

        if (!$perIpLimit->isAccepted()) {
            $this->logger->warning('Per-IP rate limit exceeded', [
                'client_ip' => $clientIp,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'retry_after_timestamp' => $perIpLimit->getRetryAfter()->getTimestamp(),
            ]);

            $event->setResponse(new Response(
                'Too Many Requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => '30']
            ));

            return;
        }

        // Check global bucket rate limit
        $globalRateLimiter = $this->globalRateLimiter->create('global');
        $globalRateLimiter = $globalRateLimiter->consume(1);

        if (!$globalRateLimiter->isAccepted()) {
            $this->logger->warning('Global bucket rate limit exceeded', [
                'client_ip' => $clientIp,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'retry_after_timestamp' => $globalRateLimiter->getRetryAfter()->getTimestamp(),
            ]);

            $event->setResponse(new Response(
                'Too Many Requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => '60']
            ));

            return;
        }
    }
}
