<?php

namespace App\Controller\Web;

use App\Entity\Content;
use App\Entity\Category;
use App\Entity\Author;
use App\Entity\Comment;
use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AppController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $feed = $em->getRepository(Content::class)->findBy([], ['trendingScore' => 'DESC'], 20);

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

        $projects = $em->getRepository(Content::class)->createQueryBuilder('c')
            ->join('c.categories', 'cat')
            ->where('cat.id = :categoryId')
            ->setParameter('categoryId', $id)
            ->orderBy('c.createdAt', 'DESC')
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
        $authors = $em->getRepository(Author::class)->findAll();
        return $this->render('app/authors.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/author/{id}', name: 'app_author', requirements: ['id' => '\d+'])]
    public function author(int $id, EntityManagerInterface $em): Response
    {
        $author = $em->getRepository(Author::class)->find($id);
        if (!$author) {
            throw $this->createNotFoundException();
        }

        // Fetch projects by this author
        // Author has telegramUserId, User has id
        $user = null;
        if ($author->getTelegramUserId()) {
            $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['id' => $author->getTelegramUserId()]);
        }

        $projects = [];
        if ($user) {
            $projects = $em->getRepository(Content::class)->findBy(['createdBy' => $user], ['createdAt' => 'DESC']);
        }

        return $this->render('app/author.html.twig', [
            'author' => $author,
            'projects' => $projects,
        ]);
    }

    #[Route('/post/{id}', name: 'app_post', requirements: ['id' => '\d+'])]
    public function post(int $id, EntityManagerInterface $em): Response
    {
        $post = $em->getRepository(Content::class)->find($id);
        if (!$post) {
            throw $this->createNotFoundException();
        }
        
        $comments = $em->getRepository(Comment::class)->findBy(['content' => $post], ['createdAt' => 'ASC']);
        
        // Build comment tree
        $commentTree = [];
        $commentMap = [];
        foreach ($comments as $comment) {
            $commentMap[$comment->getId()] = [
                'entity' => $comment,
                'children' => []
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
            'isModerator' => $isModerator
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
