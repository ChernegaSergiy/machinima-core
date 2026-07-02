<?php

namespace App\Service\Recommendation\Generator;

use App\Entity\User;
use App\Repository\PostRepository;

class FollowedAuthorsGenerator implements CandidateGeneratorInterface
{
    private PostRepository $postRepository;

    public function __construct(PostRepository $postRepository)
    {
        $this->postRepository = $postRepository;
    }

    public function generate(?User $user, int $limit = 50): array
    {
        if (!$user) {
            return [];
        }

        // TODO: Implement actual followed authors logic once the Follower entity is created.
        // For now, returning an empty array.
        // Example future implementation:
        // $followedAuthorIds = $this->followerRepository->getFollowedAuthorIds($user);
        // return $this->postRepository->findBy(['author' => $followedAuthorIds, 'isPublished' => true], ['createdAt' => 'DESC'], $limit);
        
        return [];
    }
}
