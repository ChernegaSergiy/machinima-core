<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Scorer;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\DTO\CandidatePost;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.post_scorer')]
interface PostScorerInterface
{
    /**
     * Modifies the candidate's score based on specific logic.
     *
     * @param CandidatePost $candidate The post being scored
     * @param User|null     $user      The current user (null if guest)
     */
    public function score(CandidatePost $candidate, ?User $user): void;
}
