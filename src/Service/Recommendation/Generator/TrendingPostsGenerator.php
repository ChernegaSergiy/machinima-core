<?php

namespace App\Service\Recommendation\Generator;

use App\Entity\User;
use App\Repository\PostRepository;

class TrendingPostsGenerator implements CandidateGeneratorInterface
{
    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function generate(?User $user, int $limit = 50): array
    {
        // Generates candidates based on their raw trending score
        return $this->postRepository->findBy(
            ['isPublished' => true],
            ['trendingScore' => 'DESC'],
            $limit
        );
    }
}
