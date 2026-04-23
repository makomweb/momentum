<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class LogoutHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        // Symfony's persistent remember-me handler automatically deletes tokens on logout
        // Clear session data
        $request = $event->getRequest();
        $session = $request->getSession();
        $session->remove('strava_user_id');
        $session->remove('oauth_state');
        $session->remove('remember_me_choice');
    }
}
