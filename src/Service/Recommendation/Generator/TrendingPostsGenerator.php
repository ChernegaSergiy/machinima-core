<?php

namespace App\Service\Recommendation\Generator;

use App\Entity\User;
use App\Entity\Content;
use Doctrine\ORM\EntityManagerInterface;

class TrendingPostsGenerator implements CandidateGeneratorInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function generate(?User $user, int $limit = 50): array
    {
        // For now, proxy to highest trending score
        return $this->em->getRepository(Content::class)->findBy(
            ['status' => 'published'],
            ['trendingScore' => 'DESC'],
            $limit
        );
    }
}
