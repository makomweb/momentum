<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function home(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $session = $request->getSession();
        $stravaUserId = $session->get('strava_user_id');

        return $this->render('dashboard/home.html.twig', [
            'athlete_name' => $user->getAthleteName() ?? 'Runner',
            'strava_user_id' => $stravaUserId,
        ]);
    }
}
