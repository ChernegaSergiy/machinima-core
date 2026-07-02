<?php

namespace App\Service\Recommendation\Generator;

use App\Entity\User;

interface CandidateGeneratorInterface
{
    /**
     * @param User|null $user The current user (null if guest)
     * @param int $limit Maximum number of posts to fetch
     * @return array<int, \App\Entity\Post>
     */
    public function generate(?User $user, int $limit = 50): array;
}
