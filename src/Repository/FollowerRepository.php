<?php

namespace App\Repository;

use App\Entity\Follower;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Follower>
 */
class FollowerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Follower::class);
    }

    /**
     * @return int[] Returns an array of author IDs the user is following
     */
    public function getFollowedAuthorIds(User $user): array
    {
        $qb = $this->createQueryBuilder('f')
            ->select('IDENTITY(f.author) as author_id')
            ->where('f.user = :user')
            ->setParameter('user', $user);

        $result = $qb->getQuery()->getScalarResult();

        return array_column($result, 'author_id');
    }
}
