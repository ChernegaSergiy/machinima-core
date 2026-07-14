<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Generator;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
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
