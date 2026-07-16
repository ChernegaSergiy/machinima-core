<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Notification;

use Morfeditorial\MachinimaCoreBundle\Entity\Notification;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function create(User $user, string $type, int $targetId, string $targetType, string $message): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setType($type);
        $notification->setTargetId($targetId);
        $notification->setTargetType($targetType);
        $notification->setMessage($message);

        $this->em->persist($notification);
        $this->em->flush();

        return $notification;
    }
}
