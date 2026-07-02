<?php

namespace App\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    #[Route('/api/notifications/read', name: 'api_notifications_read', methods: ['POST'])]
    public function markAllAsRead(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $em->createQuery('UPDATE App\Entity\Notification n SET n.isRead = true WHERE n.user = :user AND n.isRead = false')
           ->setParameter('user', $user)
           ->execute();

        return $this->json(['success' => true]);
    }

    #[Route('/api/notifications/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['count' => 0]);
        }
        $count = $em->getRepository(\App\Entity\Notification::class)->count(['user' => $user, 'isRead' => false]);

        return $this->json(['count' => $count]);
    }

    #[Route('/notifications/{id}/redirect', name: 'app_notification_redirect', methods: ['GET'])]
    public function readAndRedirect(int $id, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_index');
        }

        $notification = $em->getRepository(\App\Entity\Notification::class)->find($id);
        if ($notification && $notification->getUser() === $user) {
            if (!$notification->isRead()) {
                $notification->setIsRead(true);
                $em->flush();
            }

            if ($notification->getTargetId() && $notification->getTargetType()) {
                switch ($notification->getTargetType()) {
                    case 'post':
                        return $this->redirectToRoute('app_post', ['id' => $notification->getTargetId()]);
                    case 'comment':
                        $comment = $em->getRepository(\App\Entity\Comment::class)->find($notification->getTargetId());
                        if ($comment && $comment->getContent()) {
                            return $this->redirect($this->generateUrl('app_post', ['id' => $comment->getContent()->getId()]).'?scrollTo=comment-item-'.$comment->getId());
                        }
                        break;
                    case 'author':
                        return $this->redirectToRoute('app_author', ['id' => $notification->getTargetId()]);
                    case 'category':
                        return $this->redirectToRoute('app_category', ['id' => $notification->getTargetId()]);
                }
            }
        }

        return $this->redirectToRoute('app_notifications');
    }
}
