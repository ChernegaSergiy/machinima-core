<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Filter;

use Morfeditorial\MachinimaCoreBundle\Entity\User;

class DiversityFilter implements PostFilterInterface
{
    public function filter(array $candidates, ?User $user): array
    {
        $filtered = [];
        $deferred = [];
        $recentAuthors = [];

        foreach ($candidates as $candidate) {
            $staff = $candidate->getPost()->getStaff()->first();
            $authorId = $staff && $staff->getAuthor() ? $staff->getAuthor()->getId() : 0;

            $window = array_slice($recentAuthors, -5);
            $authorCountInWindow = count(array_filter($window, fn ($id) => $id === $authorId));

            if ($authorCountInWindow >= 2) {
                $deferred[] = $candidate;
                continue;
            }

            $filtered[] = $candidate;
            $recentAuthors[] = $authorId;
        }

        // Append deferred posts at the end so we don't return an empty feed
        return array_merge($filtered, $deferred);
    }
}
