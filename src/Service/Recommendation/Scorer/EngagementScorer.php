<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Scorer;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\DTO\CandidatePost;

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
