<?php

declare(strict_types=1);

namespace App\Service\Follow;

use App\Entity\Author;
use App\Entity\Follower;
use App\Entity\Notification;
use App\Entity\User;
use App\Contract\NotificationChannelPort;
use Doctrine\ORM\EntityManagerInterface;

class FollowService
{
    public function __construct(
        private EntityManagerInterface $em,
        private NotificationChannelPort $notifier,
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

                $this->notifier->send($authorUser, 'На вас підписався новий користувач у Machinima');
            }

            $this->em->flush();
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
