<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Interaction;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\ContentInteraction;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class ContentViewService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function trackView(?User $user, Content $post): void
    {
        if (!$user) {
            return;
        }

        $interaction = $this->em->getRepository(ContentInteraction::class)->findOneBy([
            'user' => $user,
            'content' => $post,
            'interactionType' => 'view',
        ]);

        $now = new \DateTime();
        $twentyFourHoursAgo = (new \DateTime('-24 hours'))->format('Y-m-d H:i:s');

        if (!$interaction || $interaction->getCreatedAt() < $twentyFourHoursAgo) {
            $post->setViewsCount($post->getViewsCount() + 1);

            if ($interaction) {
                $interaction->setCreatedAt($now->format('Y-m-d H:i:s'));
            } else {
                $interaction = new ContentInteraction();
                $interaction->setUser($user);
                $interaction->setContent($post);
                $interaction->setInteractionType('view');
                $interaction->setCreatedAt($now->format('Y-m-d H:i:s'));
                $this->em->persist($interaction);
            }

            $this->em->flush();
        }
    }
}
