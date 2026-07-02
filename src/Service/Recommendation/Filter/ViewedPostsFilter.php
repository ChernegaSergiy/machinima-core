<?php

namespace App\Service\Recommendation\Filter;

use App\Entity\User;
use App\Entity\ContentInteraction;
use App\Service\Recommendation\DTO\CandidatePost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewedPostsFilter implements PostFilterInterface
{
    private RequestStack $requestStack;
    private EntityManagerInterface $em;

    public function __construct(RequestStack $requestStack, EntityManagerInterface $em)
    {
        $this->requestStack = $requestStack;
        $this->em = $em;
    }

    public function filter(array $candidates, ?User $user): array
    {
        // Always check session first (for both guests and local caching)
        $viewedPosts = [];
        try {
            if ($this->requestStack->getMainRequest()) {
                $session = $this->requestStack->getSession();
                $viewedPosts = $session->get('viewed_posts_cooldown', []);
            }
        } catch (\Exception $e) {
            // Ignore session errors in CLI or test environment
        }
        
        $viewedIdsInDb = [];
        if ($user) {
            // Fetch posts viewed by this user in the last 7 days (to not filter out old views forever)
            $dateLimit = new \DateTimeImmutable('-7 days');
            
            $qb = $this->em->getRepository(ContentInteraction::class)->createQueryBuilder('ci');
            $results = $qb->select('IDENTITY(ci.content) as content_id')
                ->where('ci.user = :user')
                ->andWhere('ci.interactionType = :type')
                ->andWhere('ci.createdAt >= :date')
                ->setParameter('user', $user)
                ->setParameter('type', 'view')
                ->setParameter('date', $dateLimit->format('Y-m-d H:i:s'))
                ->getQuery()
                ->getScalarResult();
                
            $viewedIdsInDb = array_column($results, 'content_id');
        }

        $filtered = [];
        $deferred = [];
        
        foreach ($candidates as $candidate) {
            $postId = $candidate->getPost()->getId();
            
            // Skip if viewed in current session
            if (isset($viewedPosts[$postId])) {
                $deferred[] = $candidate;
                continue;
            }
            
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
