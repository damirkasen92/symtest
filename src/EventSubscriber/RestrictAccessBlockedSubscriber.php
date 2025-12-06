<?php

namespace App\EventSubscriber;

use App\Enum\User\Status;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;

class RestrictAccessBlockedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security
    )
    {
    }
    
    public function onRequestEvent(RequestEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user && method_exists($user, 'getStatus') && $user->getStatus() === Status::BLOCKED) {
            throw new CustomUserMessageAccountStatusException('Your account is blocked.');
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            RequestEvent::class => 'onRequestEvent',
        ];
    }
}
