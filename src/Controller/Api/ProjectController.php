<?php

namespace App\Controller\Api;

use App\Service\Project\ProjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ProjectController extends AbstractController
{
    public function __construct(
        private ProjectService $projectService,
    ) {
    }

    #[Route('/feed', name: 'api_feed', methods: ['GET'])]
    public function feed(): JsonResponse
    {
        $projects = $this->projectService->getFeed();

        return $this->json([
            'success' => true,
            'data' => array_map([$this->projectService, 'serializeProject'], $projects),
        ]);
    }

    #[Route('/projects/random', name: 'api_projects_random', methods: ['GET'])]
    public function random(): JsonResponse
    {
        $project = $this->projectService->getRandom();

        if (!$project) {
            return $this->json(['success' => false, 'error' => 'No projects found']);
        }

        return $this->json([
            'success' => true,
            'data' => $this->projectService->serializeProject($project),
        ]);
    }

    #[Route('/projects/{id}', name: 'api_project_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        $project = $this->projectService->getDetail($id);

        if (!$project) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->projectService->serializeProject($project),
        ]);
    }

    #[Route('/projects/search/{query}', name: 'api_projects_search', methods: ['GET'])]
    public function search(string $query): JsonResponse
    {
        $projects = $this->projectService->search($query);

        return $this->json([
            'success' => true,
            'data' => array_map([$this->projectService, 'serializeProject'], $projects),
        ]);
    }

    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    public function categories(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'data' => $this->projectService->getAllCategories(),
        ]);
    }

    #[Route('/categories/{id}', name: 'api_category_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function categoryDetail(int $id): JsonResponse
    {
        $data = $this->projectService->getCategory($id);

        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Category not found'], 404);
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/categories/{id}/projects', name: 'api_category_projects', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function categoryProjects(int $id, Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);

        $projects = $this->projectService->getCategoryProjects($id, $limit, $offset);

        if (null === $projects) {
            return $this->json(['success' => false, 'error' => 'Category not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => array_map([$this->projectService, 'serializeProject'], $projects),
        ]);
    }
}
