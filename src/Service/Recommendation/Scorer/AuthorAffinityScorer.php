<?php

namespace App\Service\Recommendation\Scorer;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;

class AuthorAffinityScorer implements PostScorerInterface
{
    public function score(CandidatePost $candidate, ?User $user): void
    {
        if (!$user) {
            return;
        }

        // TODO: Query a graph database or interaction history to see if the user interacts often with this author.
        // Example logic:
        // if ($this->interactionRepo->hasUserLikedAuthor($user, $candidate->getPost()->getAuthor())) {
        //     $candidate->addScore(20.0);
        // }
    }
}
