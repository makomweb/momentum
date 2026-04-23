<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Strava\RememberMeTokenService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Creates remember-me tokens in database when user successfully authenticates with "remember me" checked.
 * Injects the remember-me cookie into the response.
 */
final readonly class RememberMeTokenCreator implements EventSubscriberInterface
{
    public function __construct(
        private RememberMeTokenService $tokenService,
        private TokenStorageInterface $tokenStorage,
        private int $lifetime,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        // Only process if remember-me choice is in session (just authenticated)
        $rememberMe = $session->get('remember_me_choice');
        if (!$rememberMe) {
            return;
        }

        // Get current user
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return;
        }

        // Create token in database
        $tokenPair = $this->tokenService->createToken($user);
        $cookieValue = RememberMeTokenService::formatCookieValue($tokenPair);

        // Set cookie
        $cookie = Cookie::create(RememberMeTokenService::getCookieName())
            ->withValue($cookieValue)
            ->withExpires(time() + $this->lifetime)
            ->withPath('/')
            ->withHttpOnly(true)
            ->withSecure(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $event->getResponse()->headers->setCookie($cookie);

        // Clear session flag - only do this once
        $session->remove('remember_me_choice');
    }
}
