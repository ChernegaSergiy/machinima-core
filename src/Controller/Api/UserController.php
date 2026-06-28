<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UserController extends AbstractController
{
    #[Route('/user/{id}/notifications', name: 'api_user_notifications', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function notifications(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['id' => 'DESC'],
            50
        );

        $unreadCount = $em->getRepository(Notification::class)->count([
            'user' => $user,
            'isRead' => false
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

        return $this->json([
            'success' => true,
            'data' => [
                'notifications' => $data,
                'unread_count' => $unreadCount
            ]
        ]);
    }

    #[Route('/user/{id}/notifications/read-all', name: 'api_user_notifications_read_all', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function readAllNotifications(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $qb = $em->createQueryBuilder();
        $qb->update(Notification::class, 'n')
           ->set('n.isRead', ':read')
           ->where('n.user = :user')
           ->setParameter('read', true)
           ->setParameter('user', $user)
           ->getQuery()
           ->execute();

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/{id}/read', name: 'api_notification_read', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function readNotification(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $notif = $em->getRepository(Notification::class)->find($id);
        if (!$notif) {
            return $this->json(['success' => false, 'error' => 'Notification not found'], 404);
        }

        $notif->setIsRead(true);
        $em->flush();

        return $this->json(['success' => true]);
    }

    #[Route('/user/{id}/roles', name: 'api_user_roles', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function roles(int $id, EntityManagerInterface $em): JsonResponse
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $roles = $user->getRoles(); // Returns Symfony mapped roles like ROLE_MODERATOR
        $isModerator = in_array('ROLE_MODERATOR', $roles, true) || in_array('ROLE_ADMIN', $roles, true);

        return $this->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'is_moderator' => $isModerator
            ]
        ]);
    }
}
