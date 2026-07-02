<?php

namespace App\Service\Recommendation\Scorer;

use App\Entity\User;
use App\Repository\ContentInteractionRepository;
use App\Service\Recommendation\DTO\CandidatePost;

class AuthorAffinityScorer implements PostScorerInterface
{
    private ContentInteractionRepository $interactionRepository;

    public function __construct(ContentInteractionRepository $interactionRepository)
    {
        $this->interactionRepository = $interactionRepository;
    }

    public function score(CandidatePost $candidate, ?User $user): void
    {
        if (!$user) {
            return;
        }

        $likedAuthorIds = $this->interactionRepository->getLikedAuthorIdsByFrequency($user, 10);
        $post = $candidate->getPost();

        foreach ($post->getStaff() as $staff) {
            $authorId = $staff->getAuthor()?->getId();

            if ($authorId && in_array($authorId, $likedAuthorIds)) {
                // Determine bonus based on rank in the liked authors list (higher rank = more bonus)
                $rank = array_search($authorId, $likedAuthorIds);
                $bonus = 20.0 - ($rank * 1.5); // e.g., #1 gets 20, #2 gets 18.5, etc.

                $candidate->addScore(max(5.0, $bonus));

                return; // Add bonus once per post even if multiple authors matched
            }
        }
    }
}
