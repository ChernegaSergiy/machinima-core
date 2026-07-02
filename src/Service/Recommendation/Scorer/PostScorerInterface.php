<?php

namespace App\Service\Recommendation\Scorer;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.post_scorer')]
interface PostScorerInterface
{
    /**
     * Modifies the candidate's score based on specific logic.
     *
     * @param CandidatePost $candidate The post being scored
     * @param User|null $user The current user (null if guest)
     */
    public function score(CandidatePost $candidate, ?User $user): void;
}
