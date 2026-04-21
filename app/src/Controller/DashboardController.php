<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function home(): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        return $this->render('dashboard/home.html.twig', [
            'name' => $user->getName(),
            'strava_user_id' => $user->getStravaId(),
            'profile_picture' => $user->getProfilePictureMedium(),
        ]);
    }
}
