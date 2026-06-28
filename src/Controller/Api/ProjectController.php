<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class ProjectController extends AbstractController
{
    #[Route('/feed', name: 'api_feed', methods: ['GET'])]
    public function feed(EntityManagerInterface $em): JsonResponse
    {
        $projects = $em->getRepository(Content::class)->findBy([], ['trendingScore' => 'DESC'], 20);
        
        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'serializeProject'], $projects)
        ]);
    }

    #[Route('/projects/random', name: 'api_projects_random', methods: ['GET'])]
    public function random(EntityManagerInterface $em): JsonResponse
    {
        // Simple random: get all IDs, pick one, then fetch
        $conn = $em->getConnection();
        $sql = 'SELECT id FROM content ORDER BY RANDOM() LIMIT 1';
        $id = $conn->fetchOne($sql);

        if (!$id) {
            return $this->json(['success' => false, 'error' => 'No projects found']);
        }

        $project = $em->getRepository(Content::class)->find($id);

        return $this->json([
            'success' => true,
            'data' => $this->serializeProject($project)
        ]);
    }

    #[Route('/projects/{id}', name: 'api_project_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $project = $em->getRepository(Content::class)->find($id);

        if (!$project) {
            return $this->json(['success' => false, 'error' => 'Project not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => $this->serializeProject($project)
        ]);
    }

    #[Route('/projects/search/{query}', name: 'api_projects_search', methods: ['GET'])]
    public function search(string $query, EntityManagerInterface $em): JsonResponse
    {
        $queryBuilder = $em->getRepository(Content::class)->createQueryBuilder('c');
        $projects = $queryBuilder
            ->where('c.title LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'serializeProject'], $projects)
        ]);
    }

    #[Route('/categories', name: 'api_categories_list', methods: ['GET'])]
    public function categories(EntityManagerInterface $em): JsonResponse
    {
        $categories = $em->getRepository(Category::class)->findAll();
        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
                'parent_id' => $cat->getParent() ? $cat->getParent()->getId() : null,
            ];
        }

        return $this->json(['success' => true, 'data' => $data]);
    }

    #[Route('/categories/{id}', name: 'api_category_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function categoryDetail(int $id, EntityManagerInterface $em): JsonResponse
    {
        $cat = $em->getRepository(Category::class)->find($id);
        if (!$cat) {
            return $this->json(['success' => false, 'error' => 'Category not found'], 404);
        }

        return $this->json([
            'success' => true,
            'data' => [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
                'parent_id' => $cat->getParent() ? $cat->getParent()->getId() : null,
            ]
        ]);
    }

    #[Route('/categories/{id}/projects', name: 'api_category_projects', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function categoryProjects(int $id, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $cat = $em->getRepository(Category::class)->find($id);
        if (!$cat) {
            return $this->json(['success' => false, 'error' => 'Category not found'], 404);
        }

        $limit = $request->query->getInt('limit', 10);
        $offset = $request->query->getInt('offset', 0);

        $queryBuilder = $em->getRepository(Content::class)->createQueryBuilder('c');
        $projects = $queryBuilder
            ->join('c.categories', 'cat')
            ->where('cat.id = :catId')
            ->setParameter('catId', $id)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->json([
            'success' => true,
            'data' => array_map([$this, 'serializeProject'], $projects)
        ]);
    }

    private function serializeProject(Content $project): array
    {
        return [
            'id' => $project->getId(),
            'title' => $project->getTitle(),
            'type' => $project->getType(),
            'description' => $project->getDescription(),
            'url' => $project->getUrl(),
            'release_date' => $project->getReleaseDate(),
            'status' => $project->getStatus(),
            'cover_file_id' => $project->getCoverFileId(),
            'created_by' => $project->getCreatedBy() ? $project->getCreatedBy()->getId() : null,
            'created_at' => $project->getCreatedAt(),
            'updated_at' => $project->getUpdatedAt(),
            'likes_count' => $project->getLikesCount(),
            'dislikes_count' => $project->getDislikesCount(),
            'views_count' => $project->getViewsCount(),
            'trending_score' => $project->getTrendingScore(),
        ];
    }
}
