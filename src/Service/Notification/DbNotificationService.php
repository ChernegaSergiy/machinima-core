<?php

declare(strict_types=1);

namespace App\Service\Notification;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DbNotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function markAllAsRead(User $user): void
    {
        $this->em->createQuery('UPDATE App\Entity\Notification n SET n.isRead = true WHERE n.user = :user AND n.isRead = false')
           ->setParameter('user', $user)
           ->execute();
    }

    public function getUnreadCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        return $this->em->getRepository(Notification::class)->count(['user' => $user, 'isRead' => false]);
    }

    public function markAsReadAndGetRedirect(int $id, User $user): ?array
    {
        $notification = $this->em->getRepository(Notification::class)->find($id);
        if (!$notification || $notification->getUser() !== $user) {
            return null;
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->em->flush();
        }

        if ($notification->getTargetId() && $notification->getTargetType()) {
            return [
                'targetId' => $notification->getTargetId(),
                'targetType' => $notification->getTargetType(),
            ];
        }

        return ['targetId' => null, 'targetType' => null];
    }

    public function getUserNotifications(int $userId): ?array
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return null;
        }

        $notifications = $this->em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            50,
        );

        $unreadCount = $this->em->getRepository(Notification::class)->count([
            'user' => $user,
            'isRead' => false,
        ]);

        $data = [];
        foreach ($notifications as $notif) {
            $data[] = [
                'id' => $notif->getId(),
                'user_id' => $user->getId(),
                'type' => $notif->getType(),
                'target_id' => $notif->getTargetId(),
                'message' => $notif->getMessage(),
                'is_read' => $notif->isRead(),
                'created_at' => $notif->getCreatedAt(),
            ];
        }

        return ['notifications' => $data, 'unread_count' => $unreadCount];
    }

    public function readAllNotifications(int $userId): ?bool
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return null;
        }

        $qb = $this->em->createQueryBuilder();
        $qb->update(Notification::class, 'n')
           ->set('n.isRead', ':read')
           ->where('n.user = :user')
           ->setParameter('read', true)
           ->setParameter('user', $user)
           ->getQuery()
           ->execute();

        return true;
    }

    public function readNotification(int $id): bool
    {
        $notif = $this->em->getRepository(Notification::class)->find($id);
        if (!$notif) {
            return false;
        }

        $notif->setIsRead(true);
        $this->em->flush();

        return true;
    }
}
