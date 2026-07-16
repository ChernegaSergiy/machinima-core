<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\EventListener;

use Morfeditorial\MachinimaCoreBundle\Event\CommentCreatedEvent;
use Morfeditorial\MachinimaCoreBundle\Message\CreateNotificationMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsEventListener(event: CommentCreatedEvent::class)]
class CommentCreatedListener
{
    public function __construct(
        private MessageBusInterface $bus,
    ) {
    }

    public function __invoke(CommentCreatedEvent $event): void
    {
        $comment = $event->getComment();
        $user = $event->getUser();
        $content = $event->getContent();

        $parentUser = $comment->getParent()?->getUser();
        if ($parentUser && $parentUser->getId() !== $user->getId()) {
            $this->bus->dispatch(new CreateNotificationMessage(
                $parentUser->getId(),
                'comment_reply',
                $comment->getId(),
                'comment',
                $comment->getDisplayName().' відповів(ла) на ваш коментар.',
            ));
        }

        $projectAuthor = $content->getCreatedBy();
        if ($projectAuthor && $projectAuthor->getId() !== $user->getId()) {
            if (!$parentUser || $parentUser->getId() !== $projectAuthor->getId()) {
                $this->bus->dispatch(new CreateNotificationMessage(
                    $projectAuthor->getId(),
                    'new_comment',
                    $comment->getId(),
                    'comment',
                    'Новий коментар до вашого проєкту від '.$comment->getDisplayName().'.',
                ));
            }
        }
    }
}
