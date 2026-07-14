<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Filter;

use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\DTO\CandidatePost;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.post_filter')]
interface PostFilterInterface
{
    /**
     * Filters the candidates. Can remove posts or modify their order based on business rules.
     *
     * @param array<CandidatePost> $candidates The current pool of ranked candidates
     * @param User|null            $user       The current user (null if guest)
     *
     * @return array<CandidatePost> The filtered candidates
     */
    public function filter(array $candidates, ?User $user): array;
}
