<?php

declare(strict_types=1);

namespace App\Service\Comment;

use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\User;
use App\Service\Notification\TelegramNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class CommentService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
        private TelegramNotificationService $telegramNotifier,
    ) {
    }

    public function getProjectComments(int $projectId): ?array
    {
        $project = $this->em->getRepository(Content::class)->find($projectId);
        if (!$project) {
            return null;
        }

        $comments = $this->em->getRepository(Comment::class)->findBy(['content' => $project], ['id' => 'ASC']);

        return array_map(fn (Comment $c) => [
            'id' => $c->getId(),
            'content_id' => $project->getId(),
            'user_id' => $c->getUser()->getId(),
            'author_name' => $c->getAuthorName(),
            'text' => $c->getText(),
            'parent_id' => $c->getParent() ? $c->getParent()->getId() : null,
            'created_at' => $c->getCreatedAt(),
            'updated_at' => $c->getUpdatedAt(),
        ], $comments);
    }

    public function addComment(int $projectId, array $data): ?array
    {
        $project = $this->em->getRepository(Content::class)->find($projectId);
        if (!$project) {
            return null;
        }

        $user = $this->em->getRepository(User::class)->find((int) $data['user_id']);
        if (!$user) {
            return null;
        }

        $comment = new Comment();
        $comment->setContent($project);
        $comment->setUser($user);
        $comment->setAuthorName($data['author_name'] ?? 'Користувач');
        $comment->setText($data['text']);
        $comment->setCreatedAt(date('Y-m-d H:i:s'));

        if (!empty($data['parent_id'])) {
            $parent = $this->em->getRepository(Comment::class)->find((int) $data['parent_id']);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $this->em->persist($comment);
        $this->em->flush();

        $responseData = [
            'id' => $comment->getId(),
            'content_id' => $project->getId(),
            'user_id' => $user->getId(),
            'author_name' => $comment->getAuthorName(),
            'text' => $comment->getText(),
            'parent_id' => $comment->getParent() ? $comment->getParent()->getId() : null,
            'created_at' => $comment->getCreatedAt(),
        ];

        $this->notifyCommentReply($comment, $user);
        $this->notifyProjectAuthor($comment, $user, $project);
        $this->broadcastCommentEvent('NEW_COMMENT', $project->getId(), $responseData);

        return $responseData;
    }

    public function editComment(int $commentId, int $userId, string $text): bool
    {
        $comment = $this->em->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            return false;
        }

        if ($comment->getUser()->getId() !== $userId) {
            return false;
        }

        $comment->setText($text);
        $comment->setUpdatedAt(date('Y-m-d H:i:s'));
        $this->em->flush();

        $this->broadcastCommentEvent('EDIT_COMMENT', $commentId, [
            'comment_id' => $commentId,
            'text' => $text,
            'updated_at' => $comment->getUpdatedAt(),
        ]);

        return true;
    }

    public function deleteComment(int $commentId): ?Comment
    {
        $comment = $this->em->getRepository(Comment::class)->find($commentId);
        if (!$comment) {
            return null;
        }

        $this->em->remove($comment);
        $this->em->flush();

        $this->broadcastCommentEvent('DELETE_COMMENT', $commentId, [
            'comment_id' => $commentId,
        ]);

        return $comment;
    }

    private function notifyCommentReply(Comment $comment, User $user): void
    {
        $parentUser = $comment->getParent() ? $comment->getParent()->getUser() : null;
        if (!$parentUser || $parentUser->getId() === $user->getId()) {
            return;
        }

        $notification = new \App\Entity\Notification();
        $notification->setUser($parentUser);
        $notification->setType('comment_reply');
        $notification->setTargetId($comment->getId());
        $notification->setTargetType('comment');
        $notification->setMessage($comment->getAuthorName().' відповів(ла) на ваш коментар.');
        $this->em->persist($notification);
        $this->em->flush();

        $this->telegramNotifier->sendToUser($parentUser, 'Вам відповіли на коментар у Machinima: '.$comment->getAuthorName());
    }

    private function notifyProjectAuthor(Comment $comment, User $user, Content $project): void
    {
        $projectAuthor = $project->getCreatedBy();
        if (!$projectAuthor || $projectAuthor->getId() === $user->getId()) {
            return;
        }

        $parentUser = $comment->getParent() ? $comment->getParent()->getUser() : null;
        if ($parentUser && $parentUser->getId() === $projectAuthor->getId()) {
            return;
        }

        $notification = new \App\Entity\Notification();
        $notification->setUser($projectAuthor);
        $notification->setType('new_comment');
        $notification->setTargetId($comment->getId());
        $notification->setTargetType('comment');
        $notification->setMessage('Новий коментар до вашого проєкту від '.$comment->getAuthorName().'.');
        $this->em->persist($notification);
        $this->em->flush();

        $this->telegramNotifier->sendToUser($projectAuthor, 'Новий коментар до вашого проєкту в Machinima від '.$comment->getAuthorName());
    }

    private function broadcastCommentEvent(string $type, int $contentId, array $data): void
    {
        $update = new Update(
            'machinima/updates',
            json_encode(array_merge(['type' => $type, 'content_id' => $contentId], $data)),
        );

        try {
            $this->hub->publish($update);
        } catch (\Exception) {
        }
    }
}
