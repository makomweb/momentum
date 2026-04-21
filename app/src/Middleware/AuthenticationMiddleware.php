<?php

declare(strict_types=1);

namespace App\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;
use App\Strava\API\MissingTokenException;
use App\StravaClient;

final readonly class AuthenticationMiddleware implements EventSubscriberInterface
{
    public const string PROTECTED_ROUTE = 'requires_auth';

    public function __construct(
        private RouterInterface $router,
        private StravaClient $client,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 10],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $routeName = $request->attributes->get('_route');

        // Check if the route requires authentication
        if (!$this->isProtectedRoute($routeName)) {
            return;
        }

        $session = $request->getSession();
        $stravaUserId = $session->get('strava_user_id');

        // No user logged in, redirect to login
        if (!$stravaUserId || !is_int($stravaUserId)) {
            $response = new Response('', Response::HTTP_FOUND);
            $response->headers->set('Location', '/login');
            $event->setResponse($response);

            return;
        }

        // Verify token still exists and refresh if needed
        try {
            $this->client->refreshTokenFor($stravaUserId);
        } catch (MissingTokenException) {
            // Token cache expired, user needs to re-authenticate
            $session->invalidate();
            $response = new Response('', Response::HTTP_FOUND);
            $response->headers->set('Location', '/login');
            $event->setResponse($response);
        }
    }

    private function isProtectedRoute(mixed $routeName): bool
    {
        if (!is_string($routeName)) {
            return false;
        }

        try {
            $route = $this->router->getRouteCollection()->get($routeName);

            return $route && $route->hasRequirement(self::PROTECTED_ROUTE);
        } catch (\Throwable) {
            return false;
        }
    }
}
