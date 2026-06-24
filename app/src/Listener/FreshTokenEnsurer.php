<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use App\Strava\API\Client;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AsEventListener(event: 'kernel.request', priority: 15)]
final class FreshTokenEnsurer
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private Client $client,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $token = $this->tokenStorage->getToken();

        if (!self::isFullyAuthenticated($request, $token)) {
            $this->redirectWhenAuthenticated($event, $request, $token);
        }
    }

    private function redirectWhenAuthenticated(RequestEvent $event, Request $request, ?TokenInterface $token): void
    {
        if ($token instanceof RememberMeToken) {
            $user = $token->getUser();
            if ($user instanceof User) {
                try {
                    $this->client->refreshTokenFor($user->getStravaId());
                    self::setRedirect($event, $request);
                } catch (\Exception) {
                    /* Token exchange failed - let it pass through, error handling will catch it. */
                }
            }
        }
    }

    private static function setRedirect(RequestEvent $event, Request $request): void
    {
        $event->setResponse(
            new RedirectResponse(
                // $request->headers->get('referer') ??
                $request->getBasePath().'/activities'
            )
        );
    }

    private static function isFullyAuthenticated(Request $request, ?TokenInterface $token): bool
    {
        return $request->hasSession() && $token instanceof TokenInterface
            ? in_array('IS_AUTHENTICATED_FULLY', $token->getRoleNames(), strict: true)
            : false;
    }
}
