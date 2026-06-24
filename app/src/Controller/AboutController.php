<?php

declare(strict_types=1);

namespace App\Controller;

use App\Momentum\AthleteInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/about', name: 'about')]
final class AboutController extends AbstractController
{
    public function __construct(private AthleteInterface $athlete)
    {
    }

    public function __invoke(): Response
    {
        return $this->render('about/index.html.twig', [
            'profile_picture' => $this->athlete->getProfilePicture(),
        ]);
    }
}
