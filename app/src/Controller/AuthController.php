<?php

declare(strict_types=1);

namespace App\Controller;

use App\Strava\API\MissingTokenException;
use App\StravaClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class AuthController extends AbstractController
{
    public function __construct(private StravaClient $client)
    {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(): Response
    {
        // If already logged in, redirect to home
        if ($this->isAuthenticated()) {
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

            // Store Strava user ID in session
            $this->getSession()->set('strava_user_id', $stravaUserId);
            $this->getSession()->set('athlete_name', $token->getAthlete()->getFirstName() . ' ' . $token->getAthlete()->getLastName());

            $this->addFlash('success', sprintf('Welcome, %s! 🎉', $token->getAthlete()->getFirstName()));

            return $this->redirectToRoute('home');
        } catch (\Throwable $ex) {
            $this->addFlash('error', sprintf('Failed to authenticate: %s', $ex->getMessage()));

            return $this->redirectToRoute('login');
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST', 'GET'])]
    public function logout(): Response
    {
        $this->getSession()->invalidate();
        $this->addFlash('success', 'You have been logged out.');

        return $this->redirectToRoute('login');
    }

    private function isAuthenticated(): bool
    {
        $stravaUserId = $this->getSession()->get('strava_user_id');

        if (!$stravaUserId) {
            return false;
        }

        // Verify the token still exists in cache
        try {
            $this->client->refreshTokenFor($stravaUserId);

            return true;
        } catch (MissingTokenException) {
            // Token cache expired, user needs to re-authenticate
            $this->getSession()->invalidate();

            return false;
        }
    }

    private function getSession()
    {
        return $this->container->get('session');
    }
}
