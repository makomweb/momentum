<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'], requirements: ['requires_auth' => 'true'])]
    public function home(Request $request): Response
    {
        $session = $request->getSession();
        $athleteName = $session->get('athlete_name', 'Runner');
        $stravaUserId = $session->get('strava_user_id');

        return $this->render('dashboard/home.html.twig', [
            'athlete_name' => $athleteName,
            'strava_user_id' => $stravaUserId,
        ]);
    }
}
