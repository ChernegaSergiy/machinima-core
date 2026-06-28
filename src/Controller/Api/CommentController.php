<?php

namespace App\Controller\Api;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

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
            'data' => $data
        ]);
    }

    #[Route('/projects/{id}/comments', name: 'api_add_comment', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function addComment(int $id, Request $request, EntityManagerInterface $em, HubInterface $hub): JsonResponse
    {
        $project = $em->getRepository(Content::class)->find($id);
        if (!$project) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        $body = json_decode($request->getContent(), true);
        if (!$body || !isset($body['text'], $body['user_id'])) {
            return $this->json(['success' => false, 'error' => 'Missing data'], 400);
        }

        $user = $em->getRepository(User::class)->find((int)$body['user_id']);
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
            $parent = $em->getRepository(Comment::class)->find((int)$body['parent_id']);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $em->persist($comment);
        $em->flush();

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
                'comment' => $responseData
            ])
        );
        $hub->publish($update);

        return $this->json([
            'success' => true,
            'data' => $responseData
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
        if ($comment->getUser()->getId() !== (int)$body['user_id']) {
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
                'updated_at' => $comment->getUpdatedAt()
            ])
        );
        $hub->publish($update);

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

        $isOwner = $comment->getUser()->getId() === (int)$userId;
        
        // Check for moderator role if not owner
        $isModerator = false;
        if (!$isOwner) {
            $user = $em->getRepository(User::class)->find((int)$userId);
            if ($user && in_array('ROLE_MODERATOR', $user->getRoles(), true)) {
                $isModerator = true;
            }
        }

        if ($isOwner || $isModerator) {
            $em->remove($comment);
            $em->flush();

            $update = new Update(
                'machinima/updates',
                json_encode([
                    'type' => 'DELETE_COMMENT',
                    'comment_id' => $id
                ])
            );
            $hub->publish($update);

            return $this->json(['success' => true]);
        }

        return $this->json(['success' => false, 'error' => 'Unauthorized'], 403);
    }
}
