<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\StravaClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

final class AuthController extends AbstractController
{
    public function __construct(
        private StravaClient $client,
        private UserRepository $userRepository,
        private TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        // If already authenticated, redirect to home
        if ($this->getUser()) {
            return $this->redirectToRoute('home');
        }

        $authUrl = $this->client->getAuthorizationAddress();

        return $this->render('auth/login.html.twig', [
            'auth_url' => $authUrl,
        ]);
    }

    #[Route('/exchange-token', name: 'oauth_callback', methods: ['GET'])]
    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        $error = $request->query->get('error');

        // Handle OAuth errors from Strava
        if ($error) {
            $this->addFlash('error', sprintf('Strava auth failed: %s', $error));

            return $this->redirectToRoute('login');
        }

        if (!$code) {
            $this->addFlash('error', 'Missing authorization code from Strava');

            return $this->redirectToRoute('login');
        }

        try {
            $token = $this->client->requestToken($code);
            $stravaUserId = $token->getAthlete()->getId();
            $athleteName = $token->getAthlete()->getName();
            $email = $token->getAthlete()->getUsername();

            // Create or fetch user
            $user = $this->userRepository->findByStravaId($stravaUserId);
            if (!$user) {
                $user = new User($stravaUserId, $athleteName, $email);
                $this->userRepository->save($user, true);
            } else {
                // Update athlete info
                $user->setAthleteName($athleteName);
                $user->setEmail($email);
                $this->userRepository->save($user, true);
            }

            // Store Strava user ID in session for later API access
            $session = $request->getSession();
            $session->set('strava_user_id', $stravaUserId);

            // Create authentication token and set it for Remember Me support
            $authToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($authToken);

            // Store in session for persistence across requests
            $session->set('_security_main', serialize($authToken));

            $this->addFlash('success', sprintf('Welcome %s! 🎉', $athleteName));

            return $this->redirectToRoute('home');
        } catch (\Throwable $ex) {
            $this->addFlash('error', sprintf('Failed to authenticate: %s', $ex->getMessage()));

            return $this->redirectToRoute('login');
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST', 'GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function logout(Request $request): Response
    {
        // Clear Strava token from session (cache is session-only, auto-cleared)
        $session = $request->getSession();
        $session->remove('strava_user_id');

        // Symfony security will handle session invalidation via logout listener
        $session->invalidate();
        $this->addFlash('success', 'You have been logged out.');

        return $this->redirectToRoute('login');
    }
}
