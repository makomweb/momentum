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
        private RateLimiterFactoryInterface $limiterPerIp,
        private RateLimiterFactoryInterface $limiterGlobalBurst,
        private LoggerInterface $logger,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Skip rate limiting for health check endpoints (Kubernetes probes)
        if (str_starts_with($path, '/health/')) {
            $this->logger->debug('Skipping rate limiter for health endpoint', [
                'path' => $path,
            ]);
            return;
        }

        $this->logger->debug('Checking rate limiter for request', [
            'path' => $path,
        ]);

        $clientIp = $request->getClientIp() ?? 'unknown';

        // Check per-IP compound limiter (includes per_ten_minutes + per_thirty_seconds)
        $perIpLimiter = $this->limiterPerIp->create($clientIp);
        $perIpLimit = $perIpLimiter->consume(1);

        if (!$perIpLimit->isAccepted()) {
            $retryAfterSeconds = $perIpLimit->getRetryAfter()->getTimestamp() - time();
            $this->logger->warning('Per-IP rate limit exceeded', [
                'client_ip' => $clientIp,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'limiter' => 'compound (per_ten_minutes + per_thirty_seconds)',
                'retry_after_seconds' => $retryAfterSeconds,
            ]);

            $event->setResponse(new Response(
                'Too Many Requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $retryAfterSeconds]
            ));

            return;
        }

        // Check global burst limiter (protects against distributed attacks)
        $globalLimiter = $this->limiterGlobalBurst->create('global');
        $globalLimit = $globalLimiter->consume(1);

        if (!$globalLimit->isAccepted()) {
            $retryAfterSeconds = $globalLimit->getRetryAfter()->getTimestamp() - time();
            $this->logger->warning('Global rate limit exceeded', [
                'client_ip' => $clientIp,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'limiter' => 'global_burst',
                'retry_after_seconds' => $retryAfterSeconds,
            ]);

            $event->setResponse(new Response(
                'Too Many Requests',
                Response::HTTP_TOO_MANY_REQUESTS,
                ['Retry-After' => (string) $retryAfterSeconds]
            ));

            return;
        }
    }
}
