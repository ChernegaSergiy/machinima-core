<?php

namespace App\Controller\Web;

use App\Entity\Author;
use App\Entity\Category;
use App\Entity\Comment;
use App\Entity\Content;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(\App\Service\Recommendation\RecommendationPipeline $recommendationPipeline): Response
    {
        $feed = $recommendationPipeline->getRecommendations($this->getUser(), 20);

        return $this->render('app/index.html.twig', [
            'feed' => $feed,
        ]);
    }

    #[Route('/categories', name: 'app_categories')]
    public function categories(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('app/categories.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/category/{id}', name: 'app_category', requirements: ['id' => '\d+'])]
    public function category(int $id, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->find($id);
        if (!$category) {
            throw $this->createNotFoundException();
        }

        $qb = $em->getRepository(Content::class)->createQueryBuilder('c')
            ->join('c.categories', 'cat')
            ->where('cat.id = :categoryId')
            ->setParameter('categoryId', $id);

        if (!$this->isGranted('ROLE_MODERATOR')) {
            $qb->leftJoin('c.staff', 'cs')
               ->leftJoin('cs.author', 'a')
               ->andWhere('a.state != :privateState OR a.state IS NULL')
               ->setParameter('privateState', 'private');
        }

        $projects = $qb->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('app/category.html.twig', [
            'category' => $category,
            'projects' => $projects,
        ]);
    }

    #[Route('/authors', name: 'app_authors')]
    public function authors(EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Author::class)->createQueryBuilder('a');
        if (!$this->isGranted('ROLE_MODERATOR')) {
            $qb->andWhere('a.state != :privateState OR a.state IS NULL')
               ->setParameter('privateState', 'private');
        }
        $authors = $qb->getQuery()->getResult();

        return $this->render('app/authors.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/author/{id}', name: 'app_author', requirements: ['id' => '\d+'])]
    public function author(int $id, EntityManagerInterface $em): Response
    {
        $author = $em->getRepository(Author::class)->find($id);
        if (!$author || ('private' === $author->getState() && !$this->isGranted('ROLE_MODERATOR'))) {
            throw $this->createNotFoundException();
        }

        $projects = $em->createQueryBuilder()
            ->select('c')
            ->from(Content::class, 'c')
            ->join('c.staff', 's')
            ->where('s.author = :author')
            ->setParameter('author', $author)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $isFollowing = false;
        if ($this->getUser()) {
            $follower = $em->getRepository(\App\Entity\Follower::class)->findOneBy([
                'user' => $this->getUser(),
                'author' => $author,
            ]);
            $isFollowing = null !== $follower;
        }

        return $this->render('app/author.html.twig', [
            'author' => $author,
            'projects' => $projects,
            'isFollowing' => $isFollowing,
        ]);
    }

    #[Route('/author/{id}/follow', name: 'app_author_follow', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function followAuthor(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $author = $em->getRepository(Author::class)->find($id);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        $existing = $em->getRepository(\App\Entity\Follower::class)->findOneBy(['user' => $user, 'author' => $author]);
        if (!$existing) {
            $follower = new \App\Entity\Follower();
            $follower->setUser($user);
            $follower->setAuthor($author);
            $em->persist($follower);
            
            // Create internal notification for the author
            if ($author->getTelegramUserId()) {
                $authorUser = $em->getRepository(\App\Entity\User::class)->find($author->getTelegramUserId());
                if ($authorUser && $authorUser->getId() !== $user->getId()) {
                    $notification = new \App\Entity\Notification();
                    $notification->setUser($authorUser);
                    $notification->setType('new_follower');
                    $notification->setTargetId($user->getId());
                    $notification->setTargetType('user');
                    $notification->setMessage('На вас підписався новий користувач.');
                    $em->persist($notification);
                }
            }
            
            $em->flush();
        }

        return $this->json(['status' => 'followed']);
    }

    #[Route('/author/{id}/unfollow', name: 'app_author_unfollow', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unfollowAuthor(int $id, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $author = $em->getRepository(Author::class)->find($id);
        if (!$author) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        $existing = $em->getRepository(\App\Entity\Follower::class)->findOneBy(['user' => $user, 'author' => $author]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        return $this->json(['status' => 'unfollowed']);
    }

    #[Route('/post/{id}', name: 'app_post', requirements: ['id' => '\d+'])]
    public function post(int $id, EntityManagerInterface $em, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        $post = $em->getRepository(Content::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }

        if ($user = $this->getUser()) {
            $interaction = $em->getRepository(\App\Entity\ContentInteraction::class)->findOneBy([
                'user' => $user,
                'content' => $post,
                'interactionType' => 'view',
            ]);

            $now = new \DateTime();
            $twentyFourHoursAgo = (new \DateTime('-24 hours'))->format('Y-m-d H:i:s');

            // Count as a new view if this is the first view, or if more than 24 hours have passed
            if (!$interaction || $interaction->getCreatedAt() < $twentyFourHoursAgo) {
                $post->setViewsCount($post->getViewsCount() + 1);

                if ($interaction) {
                    $interaction->setCreatedAt($now->format('Y-m-d H:i:s'));
                } else {
                    $interaction = new \App\Entity\ContentInteraction();
                    $interaction->setUser($user);
                    $interaction->setContent($post);
                    $interaction->setInteractionType('view');
                    $interaction->setCreatedAt($now->format('Y-m-d H:i:s'));
                    $em->persist($interaction);
                }

                $em->flush();
            }
        }

        if (!$this->isGranted('ROLE_MODERATOR')) {
            foreach ($post->getStaff() as $staff) {
                if ($staff->getAuthor() && 'private' === $staff->getAuthor()->getState()) {
                    throw $this->createNotFoundException();
                }
            }
        }

        $comments = $em->getRepository(Comment::class)->findBy(['content' => $post], ['createdAt' => 'ASC']);

        // Build comment tree
        $commentTree = [];
        $commentMap = [];
        foreach ($comments as $comment) {
            $commentMap[$comment->getId()] = [
                'entity' => $comment,
                'children' => [],
            ];
        }

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

        $isModerator = false;
        if ($this->getUser()) {
            $isModerator = $this->isGranted('ROLE_MODERATOR');
        }

        return $this->render('app/post.html.twig', [
            'post' => $post,
            'commentTree' => $commentTree,
            'commentsCount' => count($comments),
            'isModerator' => $isModerator,
        ]);
    }

    #[Route('/notifications', name: 'app_notifications')]
    public function notifications(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return new Response('Unauthorized. Будь ласка, відкрийте додаток через Telegram.', 403);
        }

        $notifications = $em->getRepository(Notification::class)->findBy(
            ['user' => $user],
            ['createdAt' => 'DESC']
        );

        return $this->render('app/notifications.html.twig', [
            'notifications' => $notifications,
        ]);
    }
}
