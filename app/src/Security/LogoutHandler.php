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
        // Clear session data
        // Note: Remember-me cookie is handled automatically by Symfony's security system
        $request = $event->getRequest();
        $session = $request->getSession();
        $session->remove('strava_user_id');
        $session->remove('oauth_state');
        $session->remove('remember_me_choice');
    }
}
