<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaCoreBundle\Service\Follow;

use Morfeditorial\MachinimaCoreBundle\Entity\Author;
use Morfeditorial\MachinimaCoreBundle\Entity\Follower;
use Morfeditorial\MachinimaCoreBundle\Entity\Notification;
use Morfeditorial\MachinimaCoreBundle\Entity\User;
use Morfeditorial\MachinimaCoreBundle\Event\AuthorFollowedEvent;
use Morfeditorial\MachinimaCoreBundle\Event\AuthorUnfollowedEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class FollowService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EventDispatcherInterface $dispatcher,
    ) {
    }

    public function follow(User $user, int $authorId): ?Author
    {
        $author = $this->em->getRepository(Author::class)->find($authorId);
        if (!$author) {
            return null;
        }

        $existing = $this->em->getRepository(Follower::class)->findOneBy(['user' => $user, 'author' => $author]);
        if (!$existing) {
            $follower = new Follower();
            $follower->setUser($user);
            $follower->setAuthor($author);
            $this->em->persist($follower);

            $authorUser = $author->getUser();
            if ($authorUser && $authorUser->getId() !== $user->getId()) {
                $notification = new Notification();
                $notification->setUser($authorUser);
                $notification->setType('new_follower');
                $notification->setTargetId($user->getId());
                $notification->setTargetType('user');
                $notification->setMessage('На вас підписався новий користувач.');
                $this->em->persist($notification);
            }

            $this->em->flush();

            $this->dispatcher->dispatch(new AuthorFollowedEvent($user, $author));
        }

        return $author;
    }

    public function unfollow(User $user, int $authorId): ?Author
    {
        $author = $this->em->getRepository(Author::class)->find($authorId);
        if (!$author) {
            return null;
        }

        $existing = $this->em->getRepository(Follower::class)->findOneBy(['user' => $user, 'author' => $author]);
        if ($existing) {
            $this->em->remove($existing);
            $this->em->flush();

            $this->dispatcher->dispatch(new AuthorUnfollowedEvent($user, $author));
        }

        return $author;
    }

    public function isFollowing(User $user, int $authorId): bool
    {
        $author = $this->em->getRepository(Author::class)->find($authorId);
        if (!$author) {
            return false;
        }

        $follower = $this->em->getRepository(Follower::class)->findOneBy([
            'user' => $user,
            'author' => $author,
        ]);

        return null !== $follower;
    }
}
