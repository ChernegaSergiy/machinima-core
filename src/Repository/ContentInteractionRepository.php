<?php

namespace App\Repository;

use App\Entity\ContentInteraction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContentInteraction>
 */
class ContentInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentInteraction::class);
    }

    /**
     * Finds authors the user has interacted with by liking their content,
     * ordered by interaction frequency.
     *
     * @return int[] Returns an array of author IDs
     */
    public function getLikedAuthorIdsByFrequency(User $user, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('ci')
            ->select('IDENTITY(cs.author) as author_id, COUNT(ci.id) as interaction_count')
            ->join('ci.content', 'c')
            ->join('c.staff', 'cs')
            ->where('ci.user = :user')
            ->andWhere('ci.interactionType = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'like')
            ->groupBy('cs.author')
            ->orderBy('interaction_count', 'DESC')
            ->setMaxResults($limit);

        $result = $qb->getQuery()->getScalarResult();

        return array_column($result, 'author_id');
    }
}
