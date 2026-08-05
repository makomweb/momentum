<?php

declare(strict_types=1);

namespace App\Controller;

use App\Strava\API\Configuration;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AuthController extends AbstractController
{
    public function __construct(private Configuration $configuration)
    {
    }

    #[Route('/login', name: 'login', methods: ['GET'])]
    public function login(): Response
    {
        // If fully authenticated, redirect to activities
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('activities_index');
        }

        // Show login page for unauthenticated users
        // (RememberMe users will be handled by TokenExchangeMiddleware)
        return $this->render('auth/login.html.twig', [
            'auth_url' => $this->configuration->getAuthorizationAddress(),
        ]);
    }

    #[Route('/authorize-strava', name: 'authorize_strava', methods: ['GET', 'POST'])]
    public function authorizeStrava(Request $request): Response
    {
        // Generate OAuth state for CSRF protection
        $state = bin2hex(random_bytes(16));
        $request->getSession()->set('oauth_state', $state);

        // Check if user wants to be remembered (check both _remember_me and remember_me for compatibility)
        $rememberMe = $request->request->getBoolean('_remember_me', false)
                   || $request->request->getBoolean('remember_me', false);
        $request->getSession()->set('remember_me_choice', $rememberMe);

        // Redirect to Strava OAuth
        return $this->redirect($this->configuration->getAuthorizationAddress($state));
    }

    #[Route('/exchange-token', name: 'oauth_callback', methods: ['GET'])]
    public function callback(): Response
    {
        // This is handled by StravaAuthenticator
        // If we get here, authentication was successful
        return $this->redirectToRoute('home');
    }

    #[Route('/logout', name: 'logout', methods: ['POST', 'GET'])]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function logout(): Response
    {
        // Symfony security will handle the actual logout via logout listener
        // This method will never be reached as logout is handled by security filter
        // But Symfony requires a controller for the logout route
        return $this->redirectToRoute('login');
    }
}
