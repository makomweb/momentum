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
        // Clear Strava token from session (cache is session-only, auto-cleared)
        $request = $event->getRequest();
        $session = $request->getSession();
        $session->remove('strava_user_id');
    }
}
