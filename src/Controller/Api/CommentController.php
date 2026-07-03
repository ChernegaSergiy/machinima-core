<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\User;
use App\Service\Notification\TelegramNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CommentController extends AbstractController
{
    #[Route('/projects/{id}/comments', name: 'api_project_comments', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getComments(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Content::class)->find($id);

        if (!$project) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        $comments = $em->getRepository(Comment::class)->findBy(['content' => $project], ['id' => 'ASC']);

        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content_id' => $project->getId(),
                'user_id' => $comment->getUser()->getId(),
                'author_name' => $comment->getAuthorName(),
                'text' => $comment->getText(),
                'parent_id' => $comment->getParent() ? $comment->getParent()->getId() : null,
                'created_at' => $comment->getCreatedAt(),
                'updated_at' => $comment->getUpdatedAt(),
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    #[Route('/projects/{id}/comments', name: 'api_add_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $em, HubInterface $hub, TelegramNotificationService $telegramNotifier): JsonResponse
    {
        $project = $em->getRepository(Content::class)->find($id);
        if (!$project) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        $body = json_decode($request->getContent(), true);
        if (!$body || !isset($body['text'], $body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'Missing data'], 400);
        }

        $user = $em->getRepository(User::class)->find((int) $body['user_id']);
        if (!$user) {
            return $this->json(['success' => false, 'error' => 'User not found'], 404);
        }

        $comment = new Comment();
        $comment->setContent($project);
        $comment->setUser($user);
        $comment->setAuthorName($body['author_name'] ?? 'Користувач');
        $comment->setText($body['text']);
        $comment->setCreatedAt(date('Y-m-d H:i:s'));

        if (!empty($body['parent_id'])) {
            $parent = $em->getRepository(Comment::class)->find((int) $body['parent_id']);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $em->persist($comment);
        $em->flush();

        $parentUser = $comment->getParent() ? $comment->getParent()->getUser() : null;
        if ($parentUser && $parentUser->getId() !== $user->getId()) {
            $notification = new \App\Entity\Notification();
            $notification->setUser($parentUser);
            $notification->setType('comment_reply');
            $notification->setTargetId($comment->getId());
            $notification->setTargetType('comment');
            $notification->setMessage($comment->getAuthorName().' відповів(ла) на ваш коментар.');
            $em->persist($notification);
            $em->flush();

            $telegramNotifier->sendToUser($parentUser, 'Вам відповіли на коментар у Machinima: '.$comment->getAuthorName());
        }

        $projectAuthor = $project->getCreatedBy();
        if ($projectAuthor && $projectAuthor->getId() !== $user->getId()) {
            if (!$parentUser || $parentUser->getId() !== $projectAuthor->getId()) {
                $notification = new \App\Entity\Notification();
                $notification->setUser($projectAuthor);
                $notification->setType('new_comment');
                $notification->setTargetId($comment->getId());
                $notification->setTargetType('comment');
                $notification->setMessage('Новий коментар до вашого проєкту від '.$comment->getAuthorName().'.');
                $em->persist($notification);
                $em->flush();

                $telegramNotifier->sendToUser($projectAuthor, 'Новий коментар до вашого проєкту в Machinima від '.$comment->getAuthorName());
            }
        }

        $responseData = [
            'id' => $comment->getId(),
            'content_id' => $project->getId(),
            'user_id' => $user->getId(),
            'author_name' => $comment->getAuthorName(),
            'text' => $comment->getText(),
            'parent_id' => $comment->getParent() ? $comment->getParent()->getId() : null,
            'created_at' => $comment->getCreatedAt(),
        ];

        // Broadcast via Mercure
        $update = new Update(
            'machinima/updates',
            json_encode([
                'type' => 'NEW_COMMENT',
                'content_id' => $project->getId(),
                'comment' => $responseData,
            ])
        );
        try {
            $hub->publish($update);
        } catch (\Exception $e) {
        }

        return $this->json([
            'success' => true,
            'data' => $responseData,
        ]);
    }

    #[Route('/comments/{id}', name: 'api_edit_comment', requirements: ['id' => '\d+'], methods: ['PUT'])]
    public function editComment(int $id, Request $request, EntityManagerInterface $em, HubInterface $hub): JsonResponse
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        if (!$comment) {
            return $this->json(['success' => false, 'error' => 'Comment not found'], 404);
        }

        $body = json_decode($request->getContent(), true);
        if (!$body || !isset($body['text'], $body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'Missing data'], 400);
        }

        // Check ownership
        if ($comment->getUser()->getId() !== (int) $body['user_id']) {
            return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $comment->setText($body['text']);
        $comment->setUpdatedAt(date('Y-m-d H:i:s'));
        $em->flush();

        $update = new Update(
            'machinima/updates',
            json_encode([
                'type' => 'EDIT_COMMENT',
                'comment_id' => $comment->getId(),
                'text' => $body['text'],
                'updated_at' => $comment->getUpdatedAt(),
            ])
        );
        try {
            $hub->publish($update);
        } catch (\Exception $e) {
        }

        return $this->json(['success' => true]);
    }

    #[Route('/comments/{id}', name: 'api_delete_comment', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function deleteComment(int $id, Request $request, EntityManagerInterface $em, HubInterface $hub): JsonResponse
    {
        $comment = $em->getRepository(Comment::class)->find($id);
        if (!$comment) {
            return $this->json(['success' => false, 'error' => 'Comment not found'], 404);
        }

        $body = json_decode($request->getContent(), true);
        $userId = $body['user_id'] ?? 0;

        $isOwner = $comment->getUser()->getId() === (int) $userId;

        // Check for moderator role using native Symfony role hierarchy
        $isModerator = false;
        if ($this->getUser()) {
            $isModerator = $this->isGranted('ROLE_MODERATOR');
        }

        if ($isOwner || $isModerator) {
            $em->remove($comment);
            $em->flush();

            $update = new Update(
                'machinima/updates',
                json_encode([
                    'type' => 'DELETE_COMMENT',
                    'comment_id' => $id,
                ])
            );

            try {
                $hub->publish($update);
            } catch (\Exception $e) {
            }

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
    }
}
