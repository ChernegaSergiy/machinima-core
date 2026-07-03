<?php

namespace App\Controller\Web;

use App\Service\Profile\ProfileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    public function __construct(
        private ProfileService $profileService,
    ) {
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig',
            $this->profileService->getProfileData($this->getUser()),
        );
    }

    #[Route('/profile/following', name: 'app_profile_following')]
    #[IsGranted('ROLE_USER')]
    public function following(): Response
    {
        return $this->render('app/user_following.html.twig', [
            'authors' => $this->profileService->getFollowing($this->getUser()),
        ]);
    }

    #[Route('/profile/likes', name: 'app_profile_likes')]
    #[IsGranted('ROLE_USER')]
    public function likes(): Response
    {
        return $this->render('app/user_likes.html.twig', [
            'feed' => $this->profileService->getLikes($this->getUser()),
        ]);
    }
}
