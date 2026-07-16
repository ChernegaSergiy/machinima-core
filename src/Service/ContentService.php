<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\Category;
use Morfeditorial\MachinimaCoreBundle\Entity\Comment;
use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\ContentStaff;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Event\ContentCreatedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentUpdatedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentStatusChangedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentDeletedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\CategoryCreatedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\CategoryDeletedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentCategoryAssignedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentCategoryRemovedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentStaffAssignedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\ContentStaffRemovedEvent;
use Morfeditorial\MachinimaCoreBundle\Model\ContentItem;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ContentService
{
    private Connection $db;

    public function __construct(
        private EntityManagerInterface $em,
        private WorkflowInterface $workflow,
        private EventDispatcherInterface $dispatcher,
    ) {
        $this->db = $em->getConnection();
    }

    public function applyTransition(int $content_id, string $transition): bool
    {
        $content = $this->getContentById($content_id);
        if (!$content) {
            return false;
        }

        $contentItem = new ContentItem($content_id, $content['status']);
        $oldStatus = $content['status'];

        if ($this->workflow->can($contentItem, $transition)) {
            $this->workflow->apply($contentItem, $transition);
            $newStatus = $contentItem->status;

            $this->db->update('content', ['status' => $newStatus], ['id' => $content_id]);

            $contentEntity = $this->em->getRepository(Content::class)->find($content_id);
            if ($contentEntity) {
                $this->dispatcher->dispatch(new ContentStatusChangedEvent($contentEntity, $oldStatus, $newStatus));
            }

            return true;
        }

        return false;
    }

    public function createContent(array $data): int
    {
        $user = $this->em->getRepository(User::class)->find((int) $data['created_by']);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->db->insert('content', [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'status' => 'draft',
            'created_by' => $user->getId(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $contentId = (int) $this->db->lastInsertId();

        $content = $this->em->getRepository(Content::class)->find($contentId);
        if ($content) {
            $this->dispatcher->dispatch(new ContentCreatedEvent($content));
        }

        return $contentId;
    }

    public function updateContent(int $id, array $data): bool
    {
        $content = $this->em->getRepository(Content::class)->find($id);
        if (!$content) {
            return false;
        }

        $changedFields = [];
        if (array_key_exists('title', $data)) {
            $changedFields[] = 'title';
            $content->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $changedFields[] = 'description';
            $content->setDescription($data['description']);
        }

        $this->em->flush();

        $this->dispatcher->dispatch(new ContentUpdatedEvent($content, $changedFields));

        return true;
    }

    public function getContentById(int $id): ?array
    {
        return $this->db->fetchAssociative('SELECT * FROM content WHERE id = ?', [$id]);
    }

    public function getProjectById(int $id): ?array
    {
        $project = $this->db->fetchAssociative('SELECT * FROM content WHERE id = ?', [$id]);
        if (!$project) {
            return null;
        }

        $project['staff'] = $this->db->fetchAllAssociative(
            'SELECT cs.*, a.name as author_name, a.user_id
             FROM content_staff cs
             JOIN authors a ON cs.author_id = a.id
             WHERE cs.content_id = ?',
            [$id]
        );

        $project['categories'] = $this->db->fetchAllAssociative(
            'SELECT c.* FROM categories c
             JOIN content_categories cc ON c.id = cc.category_id
             WHERE cc.content_id = ?',
            [$id]
        );

        return $project;
    }

    public function addComment(int $content_id, int $user_id, string $text, ?int $parent_id = null): int
    {
        $this->db->insert('comments', [
            'content_id' => $content_id,
            'user_id' => $user_id,
            'text' => $text,
            'parent_id' => $parent_id,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function editComment(int $comment_id, int $user_id, string $text): bool
    {
        $comment = $this->db->fetchAssociative('SELECT * FROM comments WHERE id = ?', [$comment_id]);
        if (!$comment || (int) $comment['user_id'] !== $user_id) {
            return false;
        }

        $this->db->update('comments', ['text' => $text, 'updated_at' => date('Y-m-d H:i:s')], ['id' => $comment_id]);

        return true;
    }

    public function getCommentById(int $id): ?array
    {
        return $this->db->fetchAssociative('SELECT * FROM comments WHERE id = ?', [$id]);
    }

    public function getComments(int $content_id): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM comments WHERE content_id = ? ORDER BY id ASC', [$content_id]);
    }

    public function deleteCommentItem(int $id): bool
    {
        $comment = $this->db->fetchAssociative('SELECT * FROM comments WHERE id = ?', [$id]);
        if (!$comment) {
            return false;
        }

        $this->db->delete('comments', ['id' => $id]);

        return true;
    }

    public function getAllContent(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM content ORDER BY created_at DESC');
    }

    public function getProjectsByOwner(int $user_id): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM content WHERE created_by = ? ORDER BY created_at DESC', [$user_id]);
    }

    public function deleteContent(int $id): bool
    {
        $content = $this->em->getRepository(Content::class)->find($id);
        if (!$content) {
            return false;
        }

        $this->em->remove($content);
        $this->em->flush();

        $this->dispatcher->dispatch(new ContentDeletedEvent($content));

        return true;
    }

    public function searchContent(string $query): array
    {
        $likeQuery = '%'.$query.'%';

        return $this->db->fetchAllAssociative(
            'SELECT * FROM content WHERE (title LIKE ? OR description LIKE ?) AND status = ?',
            [$likeQuery, $likeQuery, 'published']
        );
    }

    public function getRandomContent(): ?array
    {
        $result = $this->db->fetchAllAssociative('SELECT * FROM content WHERE status = ? ORDER BY RANDOM() LIMIT 1', ['published']);

        return $result[0] ?? null;
    }

    public function getCategoryProjects(int $category_id, int $limit = 10, int $offset = 0): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT c.* FROM content c
             JOIN content_categories cc ON c.id = cc.content_id
             WHERE cc.category_id = ? AND c.status = ?
             ORDER BY c.created_at DESC
             LIMIT ? OFFSET ?',
            [$category_id, 'published', $limit, $offset]
        );
    }

    public function createCategory(string $name, ?int $parent_id = null): int
    {
        $this->db->insert('categories', [
            'name' => $name,
            'parent_id' => $parent_id,
        ]);

        $categoryId = (int) $this->db->lastInsertId();

        $category = $this->em->getRepository(Category::class)->find($categoryId);
        if ($category) {
            $this->dispatcher->dispatch(new CategoryCreatedEvent($category));
        }

        return $categoryId;
    }

    public function getCategoryById(int $id): ?array
    {
        return $this->db->fetchAssociative('SELECT * FROM categories WHERE id = ?', [$id]);
    }

    public function getAllCategories(): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM categories ORDER BY id ASC');
    }

    public function getCategoriesByParent(?int $parent_id): array
    {
        return $this->db->fetchAllAssociative('SELECT * FROM categories WHERE parent_id <=> ? ORDER BY id ASC', [$parent_id]);
    }

    public function deleteCategory(int $id): bool
    {
        $category = $this->em->getRepository(Category::class)->find($id);
        if (!$category) {
            return false;
        }

        $this->em->remove($category);
        $this->em->flush();

        $this->dispatcher->dispatch(new CategoryDeletedEvent($category));

        return true;
    }

    public function assignStaff(int $content_id, int $author_id, string $role): bool
    {
        $content = $this->em->getRepository(Content::class)->find($content_id);
        $author = $this->em->getRepository(Author::class)->find($author_id);

        if (!$content || !$author) {
            return false;
        }

        $existing = $this->db->fetchAssociative(
            'SELECT * FROM content_staff WHERE content_id = ? AND author_id = ? AND role = ?',
            [$content_id, $author_id, $role]
        );

        if ($existing) {
            return false;
        }

        $this->db->insert('content_staff', [
            'content_id' => $content_id,
            'author_id' => $author_id,
            'role' => $role,
        ]);

        $this->dispatcher->dispatch(new ContentStaffAssignedEvent($content, $author, $role));

        return true;
    }

    public function removeStaff(int $content_id, int $author_id, string $role): bool
    {
        $content = $this->em->getRepository(Content::class)->find($content_id);
        $author = $this->em->getRepository(Author::class)->find($author_id);

        if (!$content || !$author) {
            return false;
        }

        $deleted = $this->db->delete('content_staff', [
            'content_id' => $content_id,
            'author_id' => $author_id,
            'role' => $role,
        ]);

        if ($deleted > 0) {
            $this->dispatcher->dispatch(new ContentStaffRemovedEvent($content, $author, $role));
        }

        return $deleted > 0;
    }

    public function getStaffByContentId(int $content_id): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT cs.*, a.name as author_name, a.user_id
             FROM content_staff cs
             JOIN authors a ON cs.author_id = a.id
             WHERE cs.content_id = ?',
            [$content_id]
        );
    }

    public function getCategoriesByContentId(int $content_id): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT c.* FROM categories c
             JOIN content_categories cc ON c.id = cc.category_id
             WHERE cc.content_id = ?',
            [$content_id]
        );
    }

    public function assignCategory(int $content_id, int $category_id): bool
    {
        $content = $this->em->getRepository(Content::class)->find($content_id);
        $category = $this->em->getRepository(Category::class)->find($category_id);

        if (!$content || !$category) {
            return false;
        }

        $existing = $this->db->fetchAssociative(
            'SELECT * FROM content_categories WHERE content_id = ? AND category_id = ?',
            [$content_id, $category_id]
        );

        if ($existing) {
            return false;
        }

        $this->db->insert('content_categories', [
            'content_id' => $content_id,
            'category_id' => $category_id,
        ]);

        $this->dispatcher->dispatch(new ContentCategoryAssignedEvent($content, $category));

        return true;
    }

    public function removeCategory(int $content_id, int $category_id): bool
    {
        $content = $this->em->getRepository(Content::class)->find($content_id);
        $category = $this->em->getRepository(Category::class)->find($category_id);

        if (!$content || !$category) {
            return false;
        }

        $deleted = $this->db->delete('content_categories', [
            'content_id' => $content_id,
            'category_id' => $category_id,
        ]);

        if ($deleted > 0) {
            $this->dispatcher->dispatch(new ContentCategoryRemovedEvent($content, $category));
        }

        return $deleted > 0;
    }
}
