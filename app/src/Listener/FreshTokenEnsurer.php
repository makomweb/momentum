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
        if ($event->isMainRequest()) {
            $request = $event->getRequest();
            if (!$this->isFullyAuthenticated($request)) {
                $token = $this->tokenStorage->getToken();
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
        }
    }

    private static function setRedirect(RequestEvent $event, Request $request): void
    {
        $event->setResponse(
            new RedirectResponse(
                $request->headers->get('referer') ??
                $request->getBasePath().'/activities'
            )
        );
    }

    private function isFullyAuthenticated(Request $request): bool
    {
        if ($request->hasSession()) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof TokenInterface) {
                return in_array('IS_AUTHENTICATED_FULLY', $token->getRoleNames(), strict: true);
            }
        }

        return false;
    }
}
