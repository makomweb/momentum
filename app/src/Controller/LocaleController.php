<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/locale/{lang}', name: 'set_locale', requirements: ['lang' => 'en|de'])]
final class LocaleController extends AbstractController
{
    public function __invoke(string $lang, Request $request): RedirectResponse
    {
        $cookie = Cookie::create('_locale')
            ->withValue($lang)
            ->withExpires(time() + 365 * 24 * 60 * 60)
            ->withPath('/');

        $response = new RedirectResponse($request->headers->get('Referer', '/'));
        $response->headers->setCookie($cookie);

        return $response;
    }
}
