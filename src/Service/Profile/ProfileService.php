<?php

declare(strict_types=1);

namespace App\Service\Profile;

use App\Entity\Author;
use App\Entity\ContentInteraction;
use App\Entity\Follower;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProfileService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AuthorizationCheckerInterface $authChecker,
    ) {
    }

    public function getProfileData(User $user): array
    {
        $followingCount = $this->em->getRepository(Follower::class)->count(['user' => $user]);
        $likesCount = $this->em->getRepository(ContentInteraction::class)->count(['user' => $user, 'interactionType' => 'like']);
        $author = $this->em->getRepository(Author::class)->findOneBy(['user' => $user]);

        $name = $user->getDisplayName() ?? 'Користувач #'.$user->getId();

        return [
            'userData' => $user,
            'name' => $name,
            'followingCount' => $followingCount,
            'likesCount' => $likesCount,
            'myAuthorPage' => $author,
        ];
    }

    public function getFollowing(User $user): array
    {
        $followers = $this->em->getRepository(Follower::class)->findBy(['user' => $user]);
        $authors = [];
        foreach ($followers as $f) {
            if ($f->getAuthor() && $this->authChecker->isGranted('AUTHOR_VIEW', $f->getAuthor())) {
                $authors[] = $f->getAuthor();
            }
        }

        return $authors;
    }

    public function getLikes(User $user): array
    {
        $interactions = $this->em->getRepository(ContentInteraction::class)->findBy(
            ['user' => $user, 'interactionType' => 'like'],
            ['createdAt' => 'DESC']
        );

        $feed = [];
        foreach ($interactions as $i) {
            $post = $i->getContent();
            if ($post && $this->authChecker->isGranted('POST_VIEW', $post)) {
                $feed[] = $post;
            }
        }

        return $feed;
    }
}
