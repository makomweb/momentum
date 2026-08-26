<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Strava\API\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class StravaAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly Client $client,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): bool
    {
        // This authenticator supports the OAuth callback route
        return 'oauth_callback' === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $error = $request->query->get('error');
        if ($error) {
            throw new AuthenticationException(sprintf('Strava auth failed: %s', $error));
        }

        // Validate OAuth state for CSRF protection (using signed state)
        $state = $request->query->get('state');
        $stateStr = is_string($state) ? $state : '';

        if (!$stateStr || !$this->client->getConfiguration()->isStateValid($stateStr)) {
            throw new AuthenticationException('Invalid OAuth state - CSRF protection failed');
        }

        // Get authorization code
        $code = $request->query->get('code');
        if (!$code) {
            throw new AuthenticationException('Missing authorization code from Strava');
        }

        try {
            // Exchange code for token
            $token = $this->client->requestToken($code);

            // TODO: Rename to athlete!
            $athlete = $token->athlete;

            // Find or create user
            $user = $this->userRepository->findByStravaId($athlete->id);
            if (!$user) {
                $user = User::fromAthlete($athlete);
                $this->userRepository->save($user, true);
            }

            // Store strava user ID in session for later use
            $session = $request->getSession();
            $session->set('strava_user_id', $athlete->id);

            $badge = new RememberMeBadge();
            // Enable remember-me if user checked the box during OAuth initiation
            if ($session->get('remember_me_choice')) {
                $badge->enable();
                $session->remove('remember_me_choice');
            }

            return new SelfValidatingPassport(
                new UserBadge((string) $athlete->id, fn () => $user),
                [$badge],
            );
        } catch (\Throwable $ex) {
            throw new AuthenticationException(sprintf('Failed to authenticate: %s', $ex->getMessage()));
        }
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName,
    ): Response {
        // Let the firewall handle the response, remember-me middleware will set cookies
        return new RedirectResponse($request->getBasePath().'/');
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $request->getSession()->invalidate();

        return new RedirectResponse($request->getBasePath().'/login?error='.urlencode($exception->getMessage()));
    }
}
