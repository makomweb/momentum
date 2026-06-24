<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\User;
use App\Strava\API\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\RememberMeToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;

#[AsEventListener(event: 'kernel.request', priority: 15)]
final class FreshTokenEnsurer
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private Client $client,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (!self::isFullyAuthenticated($event->getRequest(), $token)) {
            if ($token instanceof RememberMeToken) {
                $this->attemptTokenRefreshAndRedirect($event, $token);
            }
        }
    }

    private function attemptTokenRefreshAndRedirect(RequestEvent $event, RememberMeToken $token): void
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return;
        }

        $stravaId = $user->getStravaId();

        try {
            $this->client->refreshTokenFor($stravaId);
            $event->setResponse(
                new RedirectResponse(
                    $event->getRequest()->getBasePath().'/activities'
                )
            );
        } catch (HttpExceptionInterface $e) {
            $this->logger->warning(
                sprintf(
                    '❌ HTTP %d during token refresh for Strava ID %s',
                    $e->getResponse()->getStatusCode(),
                    $stravaId
                ), [
                    'stravaId' => $stravaId,
                    'statusCode' => $e->getResponse()->getStatusCode(),
                ]
            );
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('💥 Unexpected error %s during token refresh', $e->getMessage()), [
                    'stravaId' => $stravaId,
                    'exception' => $e,
                ]);
        }
    }

    private static function isFullyAuthenticated(Request $request, ?TokenInterface $token): bool
    {
        return $request->hasSession() && $token instanceof TokenInterface
            ? in_array('IS_AUTHENTICATED_FULLY', $token->getRoleNames(), strict: true)
            : false;
    }
}
