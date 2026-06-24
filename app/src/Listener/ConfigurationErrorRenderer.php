<?php

declare(strict_types=1);

namespace App\Listener;

use App\Momentum\AthleteInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Twig\Environment;

#[AsEventListener(event: 'kernel.exception')]
final class ConfigurationErrorRenderer
{
    public function __construct(private Environment $twig, private AthleteInterface $athlete)
    {
    }

    public function __invoke(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpException) {
            return;
        }

        if (400 !== $exception->getStatusCode()) {
            return;
        }

        $message = $exception->getMessage();
        if (!str_contains($message, 'configure')) {
            return;
        }

        $response = new Response(
            $this->twig->render('error/configuration.html.twig', [
                'profile_picture' => $this->athlete->getProfilePicture(),
                'message' => $message,
            ]),
            Response::HTTP_BAD_REQUEST
        );

        $event->setResponse($response);
    }
}
