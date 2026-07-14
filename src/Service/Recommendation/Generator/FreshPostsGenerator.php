<?php

namespace Morfeditorial\MachinimaCoreBundle\Service\Recommendation\Generator;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class FreshPostsGenerator implements CandidateGeneratorInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function generate(?User $user, int $limit = 50): array
    {
        // Generates candidates purely based on recency
        return $this->em->getRepository(Content::class)->findBy(
            ['status' => 'published'],
            ['createdAt' => 'DESC'],
            $limit
        );
    }
}
