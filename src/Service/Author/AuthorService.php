<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Author;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\ContentStaff;
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
}
