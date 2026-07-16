<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Notification;

use Morfeditorial\MachinimaCoreBundle\Entity\Notification;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Event\NotificationReadEvent;
use Morfeditorial\MachinimaCoreBundle\Event\NotificationsMarkedAsReadEvent;
use Morfeditorial\MachinimaCoreBundle\Security\Voter\NotificationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DbNotificationService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
        private Security $security,
    ) {
    }

    public function markAllAsRead(User $user): void
    {
        $this->em->createQuery('UPDATE Morfeditorial\MachinimaCoreBundle\Entity\Notification n SET n.isRead = true WHERE n.user = :user AND n.isRead = false')
           ->setParameter('user', $user)
           ->execute();

        $this->dispatcher->dispatch(new NotificationsMarkedAsReadEvent($user));
    }

    public function getUnreadCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        return $this->em->getRepository(Notification::class)->count(['user' => $user, 'isRead' => false]);
    }

    public function markAsReadAndGetRedirect(int $id): ?array
    {
        $notification = $this->em->getRepository(Notification::class)->find($id);
        if (!$notification) {
            return null;
        }

        if (!$this->security->isGranted(NotificationVoter::EDIT, $notification)) {
            return null;
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->em->flush();

            $this->dispatcher->dispatch(new NotificationReadEvent($notification));
        }

        if (!$notification->getTargetId() || !$notification->getTargetType()) {
            return ['route' => 'app_notifications', 'params' => []];
        }

        return match ($notification->getTargetType()) {
            'post' => ['route' => 'app_post', 'params' => ['id' => $notification->getTargetId()]],
            'comment' => $this->getCommentRedirect($notification->getTargetId()),
            'author' => ['route' => 'app_author', 'params' => ['id' => $notification->getTargetId()]],
            'category' => ['route' => 'app_category', 'params' => ['id' => $notification->getTargetId()]],
            default => ['route' => 'app_notifications', 'params' => []],
        };
    }

    private function getCommentRedirect(int $commentId): array
    {
        $comment = $this->em->getRepository(\Morfeditorial\MachinimaCoreBundle\Entity\Comment::class)->find($commentId);
        if ($comment && $comment->getContent()) {
            return [
                'route' => 'app_post',
                'params' => ['id' => $comment->getContent()->getId(), '_fragment' => 'comment-item-'.$commentId],
            ];
        }

        return ['route' => 'app_notifications', 'params' => []];
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

        $this->dispatcher->dispatch(new NotificationsMarkedAsReadEvent($user));

        return true;
    }

    public function readNotification(int $id): bool
    {
        $notif = $this->em->getRepository(Notification::class)->find($id);
        if (!$notif) {
            return false;
        }

        if (!$this->security->isGranted(NotificationVoter::EDIT, $notif)) {
            return false;
        }

        $notif->setIsRead(true);
        $this->em->flush();

        $this->dispatcher->dispatch(new NotificationReadEvent($notif));

        return true;
    }
}
