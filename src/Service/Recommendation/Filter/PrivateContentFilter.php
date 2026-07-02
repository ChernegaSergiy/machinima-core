<?php

namespace App\Service\Recommendation\Filter;

use App\Entity\User;
use App\Service\Recommendation\DTO\CandidatePost;
use Symfony\Bundle\SecurityBundle\Security;

class PrivateContentFilter implements PostFilterInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function filter(array $candidates, ?User $user): array
    {
        // Moderators can see everything
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            return $candidates;
        }

        $filtered = [];
        foreach ($candidates as $candidate) {
            $post = $candidate->getPost();
            $hasPrivateAuthor = false;

            // Check if any author of this post is private
            foreach ($post->getStaff() as $staff) {
                $author = $staff->getAuthor();
                if ($author && $author->getState() === 'private') {
                    $hasPrivateAuthor = true;
                    break;
                }
            }

            if (!$hasPrivateAuthor) {
                $filtered[] = $candidate;
            }
        }

        return $filtered;
    }
}
