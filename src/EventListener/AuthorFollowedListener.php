<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\EventListener;

use Morfeditorial\MachinimaCoreBundle\Event\AuthorFollowedEvent;
use Morfeditorial\MachinimaCoreBundle\Message\CreateNotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: AuthorFollowedEvent::class)]
class AuthorFollowedListener
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(AuthorFollowedEvent $event): void
    {
        $follower = $event->getFollower();
        $author = $event->getAuthor();

        $authorUser = $author->getUser();
        if ($authorUser && $authorUser->getId() !== $follower->getId()) {
            $this->bus->dispatch(new CreateNotificationMessage(
                $authorUser->getId(),
                'new_follower',
                $follower->getId(),
                'user',
                'На вас підписався новий користувач.',
            ));
        }
    }
}
