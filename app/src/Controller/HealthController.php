<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HealthController extends AbstractController
{
    /**
     * Liveness probe endpoint - checks if app is running
     * Not subject to rate limiting.
     */
    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function liveness(): Response
    {
        return new JsonResponse(['status' => 'ok'], Response::HTTP_OK);
    }

    /**
     * Readiness probe endpoint - checks if app is ready to serve traffic
     * Not subject to rate limiting.
     */
    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function readiness(): Response
    {
        return new JsonResponse(['status' => 'ready'], Response::HTTP_OK);
    }
}
