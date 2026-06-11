<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/about', name: 'about')]
final class AboutController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('about/index.html.twig');
    }
}
