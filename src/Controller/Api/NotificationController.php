<?php

namespace Morfeditorial\MachinimaCoreBundle\Controller\Api;

use Morfeditorial\MachinimaCoreBundle\Service\Notification\DbNotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationController extends AbstractController
{
    public function __construct(
        private DbNotificationService $dbNotificationService,
    ) {
    }

    #[Route('/api/notifications/read', name: 'api_notifications_read', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function markAllAsRead(): JsonResponse
    {
        $this->dbNotificationService->markAllAsRead($this->getUser());

        return $this->json(['success' => true]);
    }

    #[Route('/api/notifications/unread-count', name: 'api_notifications_unread_count', methods: ['GET'])]
    public function unreadCount(): JsonResponse
    {
        $count = $this->dbNotificationService->getUnreadCount($this->getUser());

        return $this->json(['count' => $count]);
    }

    #[Route('/notifications/{id}/redirect', name: 'app_notification_redirect', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function readAndRedirect(int $id): Response
    {
        $redirect = $this->dbNotificationService->markAsReadAndGetRedirect($id);

        if (!$redirect) {
            return $this->redirectToRoute('app_notifications');
        }

        return $this->redirectToRoute($redirect['route'], $redirect['params']);
    }
}
