<?php

namespace App\Controller\Api;

use App\Service\Author\AuthorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/authors')]
class AuthorController extends AbstractController
{
    public function __construct(
        private AuthorService $authorService,
    ) {
    }

    #[Route('/top', name: 'api_authors_top', methods: ['GET'])]
    public function top(): JsonResponse
    {
        $authors = $this->authorService->getTop();

        return $this->json([
            'success' => true,
            'data' => array_map([$this->authorService, 'serializeAuthor'], $authors),
        ]);
    }

    #[Route('/{id}', name: 'api_author_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $author = $this->authorService->getDetail($id);

        if (!$author) {
            return $this->json(['success' => false, 'error' => 'Author not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->authorService->serializeAuthor($author),
        ]);
    }

    #[Route('/{id}/projects', name: 'api_author_projects', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function projects(int $id, Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);

        $projects = $this->authorService->getProjects($id, $limit, $offset);

        if (null === $projects) {
            return $this->json(['success' => false, 'error' => 'Author not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $projects]);
    }
}
