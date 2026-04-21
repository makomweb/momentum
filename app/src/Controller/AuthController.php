<?php

declare(strict_types=1);

namespace App\Controller;

use App\Strava\API\MissingTokenException;
use App\StravaClient;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AuthController extends AbstractController
{
    public function __construct(private StravaClient $client)
    {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(Request $request): Response
    {
        // If already logged in, redirect to home
        if ($this->isAuthenticated($request)) {
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
            $athleteName = $token->getAthlete()->getFirstName() . ' ' . $token->getAthlete()->getLastName();

            // Store Strava user ID in session
            $session = $request->getSession();
            $session->set('strava_user_id', $stravaUserId);
            $session->set('athlete_name', $athleteName);

            $this->addFlash('success', sprintf('Welcome, %s! 🎉', $token->getAthlete()->getFirstName()));

            return $this->redirectToRoute('home');
        } catch (\Throwable $ex) {
            $this->addFlash('error', sprintf('Failed to authenticate: %s', $ex->getMessage()));

            return $this->redirectToRoute('login');
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST', 'GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        $this->addFlash('success', 'You have been logged out.');

        return $this->redirectToRoute('login');
    }

    private function isAuthenticated(Request $request): bool
    {
        $session = $request->getSession();
        $stravaUserId = $session->get('strava_user_id');

        if (!$stravaUserId) {
            return false;
        }

        // Verify the token still exists in cache
        try {
            $this->client->refreshTokenFor($stravaUserId);

            return true;
        } catch (MissingTokenException) {
            // Token cache expired, user needs to re-authenticate
            $session->invalidate();

            return false;
        }
    }
}
