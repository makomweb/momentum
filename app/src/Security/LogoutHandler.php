<?php

declare(strict_types=1);

namespace App\Security;

use App\Strava\RememberMeTokenService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutHandler implements EventSubscriberInterface
{
    public function __construct(private RememberMeTokenService $rememberMeTokenService)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        $user = $token?->getUser();

        // Delete all remember-me tokens for this user
        if ($user instanceof \App\Entity\User) {
            $this->rememberMeTokenService->deleteTokensForUser($user);
        }

        // Clear Strava token from session
        $request = $event->getRequest();
        $session = $request->getSession();
        $session->remove('strava_user_id');
        $session->remove('oauth_state');
        $session->remove('remember_me_choice');
    }
}
