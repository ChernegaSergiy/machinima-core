<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Interaction;

use Morfeditorial\MachinimaCoreBundle\Entity\Content;
use Morfeditorial\MachinimaCoreBundle\Entity\ContentInteraction;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class InteractionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private HubInterface $hub,
    ) {
    }

    public function interact(int $contentId, string $type, int $userId): ?array
    {
        $content = $this->em->getRepository(Content::class)->find($contentId);
        if (!$content) {
            return null;
        }

        if ('view' === $type) {
            return ['likes' => $content->getLikesCount(), 'dislikes' => $content->getDislikesCount(), 'views' => $content->getViewsCount()];
        }

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return null;
        }

        if (!in_array($type, ['like', 'dislike'])) {
            return null;
        }

        $interaction = $this->em->getRepository(ContentInteraction::class)->createQueryBuilder('ci')
            ->where('ci.user = :user')
            ->andWhere('ci.content = :content')
            ->andWhere('ci.interactionType IN (:types)')
            ->setParameter('user', $user)
            ->setParameter('content', $content)
            ->setParameter('types', ['like', 'dislike'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($interaction) {
            if ($interaction->getInteractionType() === $type) {
                $this->em->remove($interaction);
                if ('like' === $type) {
                    $content->setLikesCount(max(0, $content->getLikesCount() - 1));
                } else {
                    $content->setDislikesCount(max(0, $content->getDislikesCount() - 1));
                }
            } else {
                $interaction->setInteractionType($type);
                if ('like' === $type) {
                    $content->setLikesCount($content->getLikesCount() + 1);
                    $content->setDislikesCount(max(0, $content->getDislikesCount() - 1));
                } else {
                    $content->setDislikesCount($content->getDislikesCount() + 1);
                    $content->setLikesCount(max(0, $content->getLikesCount() - 1));
                }
            }
        } else {
            $interaction = new ContentInteraction();
            $interaction->setUser($user);
            $interaction->setContent($content);
            $interaction->setInteractionType($type);
            $interaction->setCreatedAt(date('Y-m-d H:i:s'));
            $this->em->persist($interaction);

            if ('like' === $type) {
                $content->setLikesCount($content->getLikesCount() + 1);
            } else {
                $content->setDislikesCount($content->getDislikesCount() + 1);
            }
        }

        $this->em->flush();

        $this->broadcastStatsUpdate($contentId, $content->getLikesCount(), $content->getDislikesCount(), $content->getViewsCount());

        return [
            'likes' => $content->getLikesCount(),
            'dislikes' => $content->getDislikesCount(),
            'views' => $content->getViewsCount(),
        ];
    }

    public function getUserInteractions(int $userId): ?array
    {
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            return null;
        }

        $interactions = $this->em->getRepository(ContentInteraction::class)->findBy(['user' => $user]);
        $likedIds = [];
        $dislikedIds = [];

        foreach ($interactions as $interaction) {
            if ('like' === $interaction->getInteractionType()) {
                $likedIds[] = $interaction->getContent()->getId();
            } elseif ('dislike' === $interaction->getInteractionType()) {
                $dislikedIds[] = $interaction->getContent()->getId();
            }
        }

        return ['likes' => $likedIds, 'dislikes' => $dislikedIds];
    }

    private function broadcastStatsUpdate(int $contentId, int $likes, int $dislikes, int $views): void
    {
        $update = new Update(
            'machinima/updates',
            json_encode([
                'type' => 'STATS_UPDATE',
                'content_id' => $contentId,
                'likes' => $likes,
                'dislikes' => $dislikes,
                'views' => $views,
            ]),
        );

        try {
            $this->hub->publish($update);
        } catch (\Throwable) {
        }
    }
}
