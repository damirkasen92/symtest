<?php

namespace App\EventSubscriber;

use App\Entity\UserActivity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LogLoginActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em
    )
    {
    }

    public function onLoginSuccessEvent(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        $log = new UserActivity();
        $log->setUser($user);
        $log->setLastActivityDate(new \DateTimeImmutable());

        $this->em->persist($log);
        $this->em->flush();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccessEvent',
        ];
    }
}
