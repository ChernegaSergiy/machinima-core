<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Author;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\ContentStaff;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Event\AuthorCreatedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\AuthorUpdatedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthorService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function getTop(): array
    {
        return $this->em->getRepository(Author::class)->findBy(['state' => 'public'], ['id' => 'DESC'], 10);
    }

    public function getDetail(int $id): ?Author
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author || 'public' !== $author->getState()) {
            return null;
        }

        return $author;
    }

    public function getProjects(int $id, int $limit, int $offset): ?array
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author || 'public' !== $author->getState()) {
            return null;
        }

        $staffRecords = $this->em->getRepository(ContentStaff::class)->findBy(
            ['author' => $author],
            null,
            $limit,
            $offset,
        );

        $projects = [];
        foreach ($staffRecords as $record) {
            $project = $record->getContent();
            $projects[] = [
                'id' => $project->getId(),
                'title' => $project->getTitle(),
                'type' => $project->getType(),
                'description' => $project->getDescription(),
                'url' => $project->getUrl(),
                'cover_file_id' => $project->getCoverFileId(),
                'likes_count' => $project->getLikesCount(),
                'views_count' => $project->getViewsCount(),
                'role' => $record->getRole(),
            ];
        }

        return $projects;
    }

    public function serializeAuthor(Author $author): array
    {
        return [
            'id' => $author->getId(),
            'name' => $author->getName(),
            'biography' => $author->getBiography(),
            'channel_link' => $author->getChannelLink(),
            'created_at' => $author->getCreatedAt(),
            'state' => $author->getState(),
            'user_id' => $author->getUser()?->getId(),
        ];
    }

    public function createAuthor(string $name, ?User $user = null): Author
    {
        $author = new Author();
        $author->setName($name);
        $author->setUser($user);
        $this->em->persist($author);
        $this->em->flush();

        $this->dispatcher->dispatch(new AuthorCreatedEvent($author));

        return $author;
    }

    public function updateAuthor(int $id, array $data): bool
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author) {
            return false;
        }

        $changedFields = [];
        if (array_key_exists('name', $data)) {
            $changedFields[] = 'name';
            $author->setName($data['name']);
        }
        if (array_key_exists('biography', $data)) {
            $changedFields[] = 'biography';
            $author->setBiography($data['biography']);
        }
        if (array_key_exists('channel_link', $data)) {
            $changedFields[] = 'channel_link';
            $author->setChannelLink($data['channel_link']);
        }
        if (array_key_exists('state', $data)) {
            $changedFields[] = 'state';
            $author->setState($data['state']);
        }
        if (array_key_exists('user', $data)) {
            $changedFields[] = 'user';
            $author->setUser($data['user']);
        }

        $this->em->flush();

        $this->dispatcher->dispatch(new AuthorUpdatedEvent($author, $changedFields));

        return true;
    }

    public function deleteAuthor(int $id): bool
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author) {
            return false;
        }

        $this->em->remove($author);
        $this->em->flush();

        return true;
    }

    public function changeVisibility(int $id): bool
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author) {
            return false;
        }

        $author->setState('private' === $author->getState() ? 'public' : 'private');
        $this->em->flush();

        return true;
    }
}
