<?php

namespace App\Service\Recommendation\Generator;

use App\Entity\User;
use App\Repository\FollowerRepository;
use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;

class FollowedAuthorsGenerator implements CandidateGeneratorInterface
{
    private EntityManagerInterface $em;
    private FollowerRepository $followerRepository;

    public function __construct(EntityManagerInterface $em, FollowerRepository $followerRepository)
    {
        $this->em = $em;
        $this->followerRepository = $followerRepository;
    }

    public function generate(?User $user, int $limit = 50): array
    {
        if (!$user) {
            return [];
        }

        $followedAuthorIds = $this->followerRepository->getFollowedAuthorIds($user);
        
        if (empty($followedAuthorIds)) {
            return [];
        }

        // Fetch posts from followed authors
        $qb = $this->em->getRepository(Content::class)->createQueryBuilder('c')
            ->join('c.staff', 'cs')
            ->where('cs.author IN (:authors)')
            ->andWhere('c.isPublished = :published')
            ->setParameter('authors', $followedAuthorIds)
            ->setParameter('published', true)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}
