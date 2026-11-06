<?php

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Adds the user to lexik's login response: {token, data: {userid, name, role}}
 */
#[AsEventListener(event: Events::AUTHENTICATION_SUCCESS)]
final class TokenDataListener
{
    public function __invoke(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();
        if ($user instanceof User) {
            $event->setData($event->getData() + ['data' => $user->tokenData()]);
        }
    }
}
