<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Momentum\HeartRate;
use App\Momentum\Pace;
use App\Momentum\PaceUnit;
use App\Momentum\Power;
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
        $runTreshold = $user->getRunTreshold();
        $cyclingTreshold = $user->getCyclingTreshold();
        $maxHeartRate = $user->getMaxHeartRate();

        if ('POST' === $request->getMethod()) {
            $submittedToken = (string) $request->request->get('_token', '');
            if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('profile_settings', $submittedToken))) {
                $this->addFlash('error', 'The settings form could not be verified.');

                return $this->redirectToRoute('profile_settings');
            }

            $runTresholdRaw = trim((string) $request->request->get('run_treshold_minutes_per_kilometer', ''));
            $cyclingTresholdRaw = trim((string) $request->request->get('cycling_treshold_watts', ''));
            $maxHeartRateRaw = trim((string) $request->request->get('max_heart_rate', ''));

            $runTreshold = Pace::ZERO();
            if ('' !== $runTresholdRaw) {
                if (!is_numeric($runTresholdRaw) || (float) $runTresholdRaw <= 0) {
                    $errors[] = '$runTreshold must be a positive number.';
                } else {
                    $runTreshold = new Pace((float) $runTresholdRaw, PaceUnit::MINUTES_PER_KILOMETER);
                }
            }

            $cyclingTreshold = Power::ZERO();
            if ('' !== $cyclingTresholdRaw) {
                if (!is_numeric($cyclingTresholdRaw) || (int) $cyclingTresholdRaw <= 0) {
                    $errors[] = '$cyclingTresholdRaw must be a positive number.';
                } else {
                    $cyclingTreshold = new Power((int) $cyclingTresholdRaw);
                }
            }

            $maxHeartRate = HeartRate::ZERO();
            if ('' !== $maxHeartRateRaw) {
                if (!ctype_digit($maxHeartRateRaw) || (int) $maxHeartRateRaw <= 0) {
                    $errors[] = 'Max heart rate must be a positive integer.';
                } else {
                    $maxHeartRate = new HeartRate((int) $maxHeartRateRaw);
                }
            }

            if (empty($errors)) {
                $user->setRunTreshold($runTreshold);
                $user->setCyclingTreshold($cyclingTreshold);
                $user->setMaxHeartRate($maxHeartRate);

                $this->entityManager->flush();

                $this->addFlash('success', 'Profile settings updated.');

                return $this->redirectToRoute('profile_settings');
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->render('profile/settings.html.twig', [
            'run_treshold' => $runTreshold,
            'cycling_treshold' => $cyclingTreshold,
            'max_heart_rate' => $maxHeartRate,
            'profile_picture' => $user->getProfilePictureMedium(),
            'csrf_token' => $this->csrfTokenManager->getToken('profile_settings')->getValue(),
        ]);
    }
}
