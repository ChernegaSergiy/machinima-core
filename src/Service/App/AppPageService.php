<?php

declare(strict_types=1);

namespace App\Service\App;

use App\Entity\Author;
use App\Entity\Category;

use App\Entity\Content;
use App\Entity\Follower;
use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AppPageService
{
    public function __construct(
        private EntityManagerInterface $em,
        private AuthorizationCheckerInterface $authChecker,
    ) {
    }

    public function getCategories(): array
    {
        return $this->em->getRepository(Category::class)->findAll();
    }

    public function getCategory(int $id, ?User $user): ?array
    {
        $category = $this->em->getRepository(Category::class)->find($id);
        if (!$category) {
            return null;
        }

        $qb = $this->em->getRepository(Content::class)->createQueryBuilder('c')
            ->join('c.categories', 'cat')
            ->where('cat.id = :categoryId')
            ->setParameter('categoryId', $id);

        if (!$user || !$this->authChecker->isGranted('ROLE_MODERATOR')) {
            $qb->leftJoin('c.staff', 'cs')
               ->leftJoin('cs.author', 'a')
               ->andWhere('a.state != :privateState OR a.state IS NULL')
               ->setParameter('privateState', 'private');
        }

        $projects = $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return [
            'category' => $category,
            'projects' => $projects,
        ];
    }

    public function getAuthors(?User $user): array
    {
        $qb = $this->em->getRepository(Author::class)->createQueryBuilder('a');
        if (!$user || !$this->authChecker->isGranted('ROLE_MODERATOR')) {
            $qb->andWhere('a.state != :privateState OR a.state IS NULL')
               ->setParameter('privateState', 'private');
        }

        return $qb->getQuery()->getResult();
    }

    public function getAuthorDetail(int $id, ?User $user): ?array
    {
        $author = $this->em->getRepository(Author::class)->find($id);
        if (!$author) {
            return null;
        }

        if (!$this->authChecker->isGranted(\App\Security\Voter\AuthorVoter::VIEW, $author)) {
            return null;
        }

        $projects = $this->em->createQueryBuilder()
            ->select('c')
            ->from(Content::class, 'c')
            ->join('c.staff', 's')
            ->where('s.author = :author')
            ->setParameter('author', $author)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $isFollowing = false;
        if ($user) {
            $follower = $this->em->getRepository(Follower::class)->findOneBy([
                'user' => $user,
                'author' => $author,
            ]);
            $isFollowing = null !== $follower;
        }

        return [
            'author' => $author,
            'projects' => $projects,
            'isFollowing' => $isFollowing,
        ];
    }

    public function getPostPageData(int $id, ?User $user): ?array
    {
        $post = $this->em->getRepository(Content::class)->find($id);
        if (!$post) {
            return null;
        }

        if (!$this->authChecker->isGranted(\App\Security\Voter\PostVoter::VIEW, $post)) {
            return null;
        }

        $comments = $this->em->getRepository(Comment::class)->findBy(['content' => $post], ['createdAt' => 'ASC']);

        $commentMap = [];
        foreach ($comments as $comment) {
            $commentMap[$comment->getId()] = [
                'entity' => $comment,
                'children' => [],
            ];
        }

        $commentTree = [];
        foreach ($comments as $comment) {
            $isOrphan = false;
            if ($comment->getParent()) {
                $parentId = $comment->getParent()->getId();
                if (isset($commentMap[$parentId])) {
                    $commentMap[$parentId]['children'][] = &$commentMap[$comment->getId()];
                } else {
                    $isOrphan = true;
                }
            }

            if (!$comment->getParent() || $isOrphan) {
                $commentTree[] = &$commentMap[$comment->getId()];
            }
        }

        $isModerator = $user && $this->authChecker->isGranted('ROLE_MODERATOR');

        return [
            'post' => $post,
            'commentTree' => $commentTree,
            'commentsCount' => count($comments),
            'isModerator' => $isModerator,
        ];
    }

    public function getNotifications(User $user): array
    {
        return $this->em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );
    }

    public function getUserProfile(int $id, ?User $currentUser): ?array
    {
        if ($currentUser && $currentUser->getId() === $id) {
            return ['self' => true];
        }

        $targetUser = $this->em->getRepository(User::class)->find($id);
        if (!$targetUser) {
            return null;
        }

        $followingCount = $this->em->getRepository(Follower::class)->count(['user' => $targetUser]);
        $likesCount = $this->em->getRepository(\App\Entity\ContentInteraction::class)->count(['user' => $targetUser, 'interactionType' => 'like']);

        $name = $targetUser->getDisplayName() ?? 'Користувач #'.$targetUser->getId();

        $author = $this->em->getRepository(Author::class)->findOneBy(['user' => $targetUser]);

        return [
            'targetUser' => $targetUser,
            'followingCount' => $followingCount,
            'likesCount' => $likesCount,
            'name' => $name,
            'author' => $author,
        ];
    }
}
