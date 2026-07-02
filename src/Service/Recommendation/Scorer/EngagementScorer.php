<?php

namespace App\Service\Recommendation\Scorer;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;

class EngagementScorer implements PostScorerInterface
{
    public function score(CandidatePost $candidate, ?User $user): void
    {
        $post = $candidate->getPost();
        
        // Use the pre-calculated trending score from the database as a base metric for engagement
        $trendingScore = $post->getTrendingScore() ?? 0.0;
        
        // We can amplify or normalize this score. For example, add it directly.
        $candidate->addScore($trendingScore);
    }
}
