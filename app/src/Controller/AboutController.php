<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/about', name: 'about')]
final class AboutController extends AbstractController
{
    public function __invoke(): Response
    {
        $data = [];

        $user = $this->getUser();
        if ($user instanceof User) {
            $data['profile_picture'] = $user->getProfilePictureMedium();
        }

        return $this->render('about/index.html.twig', $data);
    }
}
