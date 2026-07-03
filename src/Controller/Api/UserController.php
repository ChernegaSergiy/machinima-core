<?php

namespace App\Controller\Api;

use App\Service\Notification\DbNotificationService;
use App\Service\User\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private DbNotificationService $dbNotificationService,
        private UserService $userService,
    ) {
    }

    #[Route('/user/{id}/notifications', name: 'api_user_notifications', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function notifications(int $id): JsonResponse
    {
        $result = $this->dbNotificationService->getUserNotifications($id);

        if (!$result) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $result]);
    }

    #[Route('/user/{id}/notifications/read-all', name: 'api_user_notifications_read_all', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function readAllNotifications(int $id): JsonResponse
    {
        $success = $this->dbNotificationService->readAllNotifications($id);

        if (null === $success) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/notifications/{id}/read', name: 'api_notification_read', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function readNotification(int $id): JsonResponse
    {
        $success = $this->dbNotificationService->readNotification($id);

        if (!$success) {
            return $this->json(['success' => false, 'error' => 'Notification not found'], 404);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/user/{id}/roles', name: 'api_user_roles', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function roles(int $id): JsonResponse
    {
        $result = $this->userService->getRoles($id);

        if (!$result) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $result]);
    }
}
