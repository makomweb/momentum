<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\Pace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile', name: 'profile_')]
final class ProfileSettingsController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CsrfTokenManagerInterface $csrfTokenManager,
    ) {
    }

    #[Route('/settings', name: 'settings', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function settings(Request $request): Response
    {
        $user = $this->getUser();
        assert($user instanceof User);

        $errors = [];
        $ftp = $user->getFtp();
        $maxHeartRate = $user->getMaxHeartRate();

        if ('POST' === $request->getMethod()) {
            $submittedToken = (string) $request->request->get('_token', '');
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('profile_settings', $submittedToken))) {
                $this->addFlash('error', 'The settings form could not be verified.');

                return $this->redirectToRoute('profile_settings');
            }

            $ftpRaw = trim((string) $request->request->get('ftp_minutes_per_kilometer', ''));
            $maxHeartRateRaw = trim((string) $request->request->get('max_heart_rate', ''));

            $ftp = new Pace(0.0);
            if ('' !== $ftpRaw) {
                if (!is_numeric($ftpRaw) || (float) $ftpRaw <= 0) {
                    $errors[] = 'FTP must be a positive number.';
                } else {
                    $ftp = new Pace((float) $ftpRaw);
                }
            }

            $maxHeartRate = 0;
            if ('' !== $maxHeartRateRaw) {
                if (!ctype_digit($maxHeartRateRaw) || (int) $maxHeartRateRaw <= 0) {
                    $errors[] = 'Max heart rate must be a positive integer.';
                } else {
                    $maxHeartRate = (int) $maxHeartRateRaw;
                }
            }

            if ([] === $errors) {
                $user->setFtp($ftp);
                $user->setMaxHeartRate($maxHeartRate);

                $this->entityManager->flush();

                $this->addFlash('success', 'Profile settings updated.');

                return $this->redirectToRoute('profile_settings');
            }

            $maxHeartRate = '' === $maxHeartRateRaw ? null : (int) $maxHeartRateRaw;

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('profile/settings.html.twig', [
            'ftp' => $ftp,
            'maxHeartRate' => $maxHeartRate,
            'csrf_token' => $this->csrfTokenManager->getToken('profile_settings')->getValue(),
        ]);
    }
}
