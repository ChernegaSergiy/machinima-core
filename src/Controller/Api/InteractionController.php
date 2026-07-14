<?php

namespace Morfeditorial\MachinimaCoreBundle\Controller\Api;

use Morfeditorial\MachinimaCoreBundle\Service\Interaction\InteractionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class InteractionController extends AbstractController
{
    public function __construct(
        private InteractionService $interactionService,
    ) {
    }

    #[Route('/interact', name: 'api_interact', methods: ['POST'])]
    public function interact(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);

        if (!isset($body['content_id'], $body['type'])) {
            return $this->json(['success' => false, 'error' => 'Missing content_id or type'], 400);
        }

        if ('view' === $body['type']) {
            return $this->json(['success' => false, 'error' => 'View tracking is now handled server-side'], 400);
        }

        if (!isset($body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'User ID is required for liking/disliking'], 400);
        }

        $result = $this->interactionService->interact(
            (int) $body['content_id'],
            (string) $body['type'],
            (int) $body['user_id'],
        );

        if (!$result) {
            return $this->json(['success' => false, 'error' => 'Content or user not found'], 404);
        }

        return $this->json(array_merge(['success' => true], $result));
    }

    #[Route('/user/{userId}/interactions', name: 'api_user_interactions', methods: ['GET'])]
    public function getUserInteractions(int $userId): JsonResponse
    {
        $result = $this->interactionService->getUserInteractions($userId);

        if (!$result) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        return $this->json(array_merge(['success' => true], $result));
    }
}
