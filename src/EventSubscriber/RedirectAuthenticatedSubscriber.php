<?php

namespace App\EventSubscriber;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class RedirectAuthenticatedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security
    )
    {
    }

    public function onRequestEvent(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (
            $this->security->getUser() 
            && \in_array(
                    $route, 
                    ['app_login_show', 'app_register_show', 'app_register'], 
                    true
                )
        ) {
            $event->setResponse(new RedirectResponse('/'));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
