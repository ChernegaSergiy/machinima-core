<?php

namespace App\Service\Recommendation\Filter;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;

class DiversityFilter implements PostFilterInterface
{
    public function filter(array $candidates, ?User $user): array
    {
        $filtered = [];
        $recentAuthors = [];
        
        foreach ($candidates as $candidate) {
            $authorId = $candidate->getPost()->getAuthor()->getId();
            
            // If this author has appeared in the last 2 posts, we might want to defer this post
            // For simplicity in this example, we just enforce a simple rule:
            // No author can appear more than twice in any block of 5 posts.
            
            // This is a basic implementation of a sliding window heuristic.
            $window = array_slice($recentAuthors, -5);
            $authorCountInWindow = count(array_filter($window, fn($id) => $id === $authorId));
            
            if ($authorCountInWindow >= 2) {
                // Skip or defer. For this basic example, we drop it to encourage diversity.
                continue;
            }
            
            $filtered[] = $candidate;
            $recentAuthors[] = $authorId;
        }
        
        return $filtered;
    }
}
