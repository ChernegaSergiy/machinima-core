<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\MessageHandler;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Message\CreateNotificationMessage;
use Morfeditorial\MachinimaCoreBundle\Service\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateNotificationHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationService $notificationService,
    ) {
    }

    public function __invoke(CreateNotificationMessage $message): void
    {
        $user = $this->em->getRepository(User::class)->find($message->getUserId());
        if (!$user) {
            return;
        }

        $this->notificationService->create(
            $user,
            $message->getType(),
            $message->getTargetId(),
            $message->getTargetType(),
            $message->getMessage(),
        );
    }
}
