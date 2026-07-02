<?php

namespace App\Service\Recommendation\Scorer;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;

class TimeDecayScorer implements PostScorerInterface
{
    public function score(CandidatePost $candidate, ?User $user): void
    {
        $createdAt = $candidate->getPost()->getCreatedAt();

        if (!$createdAt) {
            return;
        }

        $timestamp = is_string($createdAt) ? strtotime($createdAt) : $createdAt->getTimestamp();
        $hoursOld = (time() - $timestamp) / 3600;

        // Exponential decay formula: Score penalty increases as the post gets older
        // Example: -0.5 points for every hour old, capping at -50 points
        $penalty = min(50.0, $hoursOld * 0.5);

        $candidate->subtractScore($penalty);
    }
}
