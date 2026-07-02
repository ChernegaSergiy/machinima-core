<?php

namespace App\Service\Recommendation\Filter;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;
use Symfony\Component\HttpFoundation\RequestStack;

class ViewedPostsFilter implements PostFilterInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function filter(array $candidates, ?User $user): array
    {
        $session = $this->requestStack->getSession();
        $viewedPosts = $session->get('viewed_posts_cooldown', []);
        
        // Remove posts whose IDs exist in the viewed session cache
        return array_filter($candidates, function (CandidatePost $candidate) use ($viewedPosts) {
            return !isset($viewedPosts[$candidate->getPost()->getId()]);
        });
    }
}
