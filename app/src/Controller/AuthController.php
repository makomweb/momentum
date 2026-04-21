<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Strava\Client;
use App\Strava\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AuthController extends AbstractController
{
    public function __construct(
        private Configuration $configuration,
        private Client $client,
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

        return $this->render('auth/login.html.twig', [
            'auth_url' => $this->configuration->getAuthorizationAddress(),
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
            $stravaUser = $token->getAthlete();

            // Create or fetch user
            $user = $this->userRepository->findByStravaId($stravaUser->getId());
            if (!$user) {
                $user = User::fromStravaUser($stravaUser);
                $this->userRepository->save($user, true);
            }

            // Store Strava user ID in session for later API access
            $session = $request->getSession();
            $session->set('strava_user_id', $stravaUser->getId());

            // Create authentication token and set it for Remember Me support
            $authToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
            $this->tokenStorage->setToken($authToken);

            // Store in session for persistence across requests
            $session->set('_security_main', serialize($authToken));

            $this->addFlash('success', sprintf('Welcome %s! 🎉', $user->getName()));

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
        // Symfony security will handle the actual logout via logout listener
        // This method will never be reached as logout is handled by security filter
        // But Symfony requires a controller for the logout route
        $this->addFlash('success', 'You have been logged out.');
        return $this->redirectToRoute('login');
    }
}
