<?php

namespace App\Controller\Api;

use App\Entity\Author;
use App\Entity\ContentStaff;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/authors')]
class AuthorController extends AbstractController
{
    #[Route('/top', name: 'api_authors_top', methods: ['GET'])]
    public function top(EntityManagerInterface $em): JsonResponse
    {
        // Simple mock for top authors: just get public authors ordered by ID
        $authors = $em->getRepository(Author::class)->findBy(['state' => 'public'], ['id' => 'DESC'], 10);

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'serializeAuthor'], $authors),
        ]);
    }

    #[Route('/{id}', name: 'api_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $author = $em->getRepository(Author::class)->find($id);

        if (!$author || 'public' !== $author->getState()) {
            return $this->json(['success' => false, 'error' => 'Author not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->serializeAuthor($author),
        ]);
    }

    #[Route('/{id}/projects', name: 'api_author_projects', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function projects(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $author = $em->getRepository(Author::class)->find($id);

        if (!$author || 'public' !== $author->getState()) {
            return $this->json(['success' => false, 'error' => 'Author not found'], 404);
        }

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);

        // Get projects where this author is staff
        $staffRecords = $em->getRepository(ContentStaff::class)->findBy(
            ['author' => $author],
            null,
            $limit,
            $offset
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
                'role' => $record->getRole(), // Include the role the author had in this project
            ];
        }

        return $this->json([
            'success' => true,
            'data' => $projects,
        ]);
    }

    private function serializeAuthor(Author $author): array
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
