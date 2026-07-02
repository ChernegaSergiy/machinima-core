<?php

namespace App\Controller\Web;

use App\Entity\Author;
use App\Entity\ContentInteraction;
use App\Entity\Follower;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $userId = $user->getId();

        $followingCount = $this->entityManager->getRepository(Follower::class)->count(['user' => $user]);
        $likesCount = $this->entityManager->getRepository(ContentInteraction::class)->count(['user' => $user, 'interactionType' => 'like']);

        $author = $this->entityManager->getRepository(Author::class)->findOneBy(['telegramUserId' => $userId]);

        return $this->render('profile/index.html.twig', [
            'userData' => $user,
            'followingCount' => $followingCount,
            'likesCount' => $likesCount,
            'myAuthorPage' => $author,
        ]);
    }

    #[Route('/profile/following', name: 'app_profile_following')]
    #[IsGranted('ROLE_USER')]
    public function following(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $followers = $this->entityManager->getRepository(Follower::class)->findBy(['user' => $user]);
        $authors = [];
        foreach ($followers as $f) {
            if ($f->getAuthor() && $this->isGranted('AUTHOR_VIEW', $f->getAuthor())) {
                $authors[] = $f->getAuthor();
            }
        }

        return $this->render('app/user_following.html.twig', [
            'authors' => $authors,
        ]);
    }

    #[Route('/profile/likes', name: 'app_profile_likes')]
    #[IsGranted('ROLE_USER')]
    public function likes(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $interactions = $this->entityManager->getRepository(ContentInteraction::class)->findBy(
            ['user' => $user, 'interactionType' => 'like'],
            ['createdAt' => 'DESC']
        );
        $feed = [];
        foreach ($interactions as $i) {
            $post = $i->getContent();
            if ($post && $this->isGranted('POST_VIEW', $post)) {
                $feed[] = $post;
            }
        }

        return $this->render('app/user_likes.html.twig', [
            'feed' => $feed,
        ]);
    }
}
