<?php

namespace Symfony\Component\Security\Http\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Http\Event\VerifyAuthenticatorCredentialsEvent;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 *
 * @final
 * @experimental in 5.1
 */
class UserCheckerListener implements EventSubscriberInterface
{
    private $userChecker;

    public function __construct(UserCheckerInterface $userChecker)
    {
        $this->userChecker = $userChecker;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            VerifyAuthenticatorCredentialsEvent::class => [
                ['preCredentialsVerification', 256],
                ['preCredentialsVerification', 32]
            ],
        ];
    }

    public function preCredentialsVerification(VerifyAuthenticatorCredentialsEvent $event): void
    {
        $this->userChecker->checkPreAuth($event->getUser());
    }

    public function postCredentialsVerification(VerifyAuthenticatorCredentialsEvent $event): void
    {
        $this->userChecker->checkPostAuth($event->getUser());
    }
}
