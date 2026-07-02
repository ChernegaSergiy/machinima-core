<?php

namespace App\Service\Recommendation\Filter;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.post_filter')]
interface PostFilterInterface
{
    /**
     * Filters the candidates. Can remove posts or modify their order based on business rules.
     *
     * @param array<CandidatePost> $candidates The current pool of ranked candidates
     * @param User|null $user The current user (null if guest)
     * @return array<CandidatePost> The filtered candidates
     */
    public function filter(array $candidates, ?User $user): array;
}
