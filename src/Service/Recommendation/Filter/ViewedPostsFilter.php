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
        $session = $this->requestStack->getSession();
        $viewedPosts = $session->get('viewed_posts_cooldown', []);
        
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
            // Flip array for fast isset lookup
            $viewedIdsInDb = array_flip($viewedIdsInDb);
        }

        return array_filter($candidates, function (CandidatePost $candidate) use ($viewedPosts, $viewedIdsInDb) {
            $postId = $candidate->getPost()->getId();
            
            if (isset($viewedPosts[$postId])) {
                return false; // Viewed in current session
            }
            
            if (isset($viewedIdsInDb[$postId])) {
                return false; // Viewed recently according to DB
            }
            
            return true;
        });
    }
}
