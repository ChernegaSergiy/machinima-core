<?php

namespace App\Controller\Web;

use App\Entity\UserData;
use App\Entity\UserFollower;
use App\Entity\ContentInteraction;
use App\Entity\Author;
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
        /** @var UserData $user */
        $user = $this->getUser();
        $userId = $user->getUserId();

        $followingCount = $this->entityManager->getRepository(UserFollower::class)->count(['user' => $user]);
        $likesCount = $this->entityManager->getRepository(ContentInteraction::class)->count(['user' => $user, 'interactionType' => 'like']);
        
        $author = $this->entityManager->getRepository(Author::class)->findOneBy(['telegramUserId' => $userId]);

        return $this->render('profile/index.html.twig', [
            'userData' => $user,
            'followingCount' => $followingCount,
            'likesCount' => $likesCount,
            'myAuthorPage' => $author,
        ]);
    }
}
