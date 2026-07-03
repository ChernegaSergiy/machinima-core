<?php

namespace App\Controller\Api;

use App\Service\Comment\CommentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CommentController extends AbstractController
{
    public function __construct(
        private CommentService $commentService,
    ) {
    }

    #[Route('/projects/{id}/comments', name: 'api_project_comments', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getComments(int $id): JsonResponse
    {
        $comments = $this->commentService->getProjectComments($id);

        if (null === $comments) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $comments]);
    }

    #[Route('/projects/{id}/comments', name: 'api_add_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addComment(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!$body || !isset($body['text'], $body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'Missing data'], 400);
        }

        $result = $this->commentService->addComment($id, $body);

        if (!$result) {
            return $this->json(['success' => false, 'error' => 'Project or user not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $result]);
    }

    #[Route('/comments/{id}', name: 'api_edit_comment', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function editComment(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        if (!$body || !isset($body['text'], $body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'Missing data'], 400);
        }

        $success = $this->commentService->editComment($id, (int) $body['user_id'], $body['text']);

        if (!$success) {
            return $this->json(['success' => false, 'error' => 'Comment not found or unauthorized'], 404);
        }

        return $this->json(['success' => true]);
    }

    #[Route('/comments/{id}', name: 'api_delete_comment', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteComment(int $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true);
        $userId = (int) ($body['user_id'] ?? 0);
        $isModerator = $this->getUser() && $this->isGranted('ROLE_MODERATOR');

        $success = $this->commentService->deleteComment($id, $userId, $isModerator);

        if (!$success) {
            return $this->json(['success' => false, 'error' => 'Comment not found or unauthorized'], 404);
        }

        return $this->json(['success' => true]);
    }
}
