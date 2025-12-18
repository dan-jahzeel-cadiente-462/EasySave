<?php

namespace App\EventSubscriber;

use App\Service\ActivityLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class SecurityEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private ActivityLogService $activityLogService) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        
        if ($user) {
            $this->activityLogService->logLogin($user);
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $token = $event->getToken();
        
        if ($token && $token->getUser()) {
            $user = $token->getUser();
            $this->activityLogService->logLogout($user);
        }
    }
}
