<?php

declare(strict_types=1);

namespace App\Service\Project;

use App\Entity\Category;
use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;

class ProjectService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function getFeed(): array
    {
        return $this->em->getRepository(Content::class)->findBy([], ['trendingScore' => 'DESC'], 20);
    }

    public function getRandom(): ?Content
    {
        $conn = $this->em->getConnection();
        $sql = 'SELECT id FROM content ORDER BY RANDOM() LIMIT 1';
        $id = $conn->fetchOne($sql);

        if (!$id) {
            return null;
        }

        return $this->em->getRepository(Content::class)->find($id);
    }

    public function getDetail(int $id): ?Content
    {
        return $this->em->getRepository(Content::class)->find($id);
    }

    public function search(string $query): array
    {
        $queryBuilder = $this->em->getRepository(Content::class)->createQueryBuilder('c');

        return $queryBuilder
            ->where('c.title LIKE :query OR c.description LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function getAllCategories(): array
    {
        $categories = $this->em->getRepository(Category::class)->findAll();
        $data = [];
        foreach ($categories as $cat) {
            $data[] = [
                'id' => $cat->getId(),
                'name' => $cat->getName(),
                'parent_id' => $cat->getParent() ? $cat->getParent()->getId() : null,
            ];
        }

        return $data;
    }

    public function getCategory(int $id): ?array
    {
        $cat = $this->em->getRepository(Category::class)->find($id);
        if (!$cat) {
            return null;
        }

        return [
            'id' => $cat->getId(),
            'name' => $cat->getName(),
            'parent_id' => $cat->getParent() ? $cat->getParent()->getId() : null,
        ];
    }

    public function getCategoryProjects(int $id, int $limit, int $offset): ?array
    {
        $cat = $this->em->getRepository(Category::class)->find($id);
        if (!$cat) {
            return null;
        }

        $queryBuilder = $this->em->getRepository(Content::class)->createQueryBuilder('c');

        return $queryBuilder
            ->join('c.categories', 'cat')
            ->where('cat.id = :catId')
            ->setParameter('catId', $id)
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function serializeProject(Content $project): array
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
