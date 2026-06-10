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

#[AsEventListener(event: 'kernel.request', priority: 15)]
final class FreshTokenEnsurer
{
    public function __construct(
        private Client $client,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip if already fully authenticated
        if ($this->isFullyAuthenticated($request)) {
            return;
        }

        // Get security token
        $token = $this->tokenStorage->getToken();

        // Check if we have a RememberMe token
        if (!$token instanceof RememberMeToken) {
            return;
        }

        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        try {
            // Try to exchange/refresh the token
            $this->client->refreshTokenFor($user->getStravaId());

            // Redirect to referrer or activities
            $referrer = $request->headers->get('referer');
            if (!$referrer) {
                $referrer = $request->getBasePath().'/activities';
            }
            $event->setResponse(new RedirectResponse($referrer));
        } catch (\Exception) {
            // Token exchange failed - let it pass through, error handling will catch it
        }
    }

    /**
     * Check if the user is fully authenticated.
     */
    private function isFullyAuthenticated(Request $request): bool
    {
        if (!$request->hasSession()) {
            return false;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return in_array('IS_AUTHENTICATED_FULLY', $token->getRoleNames(), strict: true);
    }
}
