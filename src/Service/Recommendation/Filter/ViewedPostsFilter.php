<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Filter;

use Morfeditorial\MachinimaCoreBundle\Entity\ContentInteraction;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ViewedPostsFilter implements PostFilterInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function filter(array $candidates, ?User $user): array
    {
        $viewedIdsInDb = [];
        if ($user) {
            // Fetch posts viewed by this user in the last 7 days (to not filter out old views forever)
            $dateLimit = new \DateTimeImmutable('-7 days');
            $recentLimit = new \DateTimeImmutable('-15 minutes'); // Do not defer very recent views (preserves scroll memory on back button)

            $qb = $this->em->getRepository(ContentInteraction::class)->createQueryBuilder('ci');
            $results = $qb->select('IDENTITY(ci.content) as content_id')
                ->where('ci.user = :user')
                ->andWhere('ci.interactionType = :type')
                ->andWhere('ci.createdAt >= :dateLimit')
                ->andWhere('ci.createdAt <= :recentLimit')
                ->setParameter('user', $user)
                ->setParameter('type', 'view')
                ->setParameter('dateLimit', $dateLimit->format('Y-m-d H:i:s'))
                ->setParameter('recentLimit', $recentLimit->format('Y-m-d H:i:s'))
                ->getQuery()
                ->getScalarResult();

            $viewedIdsInDb = array_column($results, 'content_id');
        }

        $filtered = [];
        $deferred = [];

        foreach ($candidates as $candidate) {
            $postId = $candidate->getPost()->getId();

            // Skip if viewed in the last 7 days (from DB)
            if (in_array($postId, $viewedIdsInDb)) {
                $deferred[] = $candidate;
                continue;
            }

            $filtered[] = $candidate;
        }

        return array_merge($filtered, $deferred);
    }
}
