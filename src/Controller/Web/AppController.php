<?php

namespace Morfeditorial\MachinimaCoreBundle\Controller\Web;

use Morfeditorial\MachinimaCoreBundle\Service\App\AppPageService;
use Morfeditorial\MachinimaCoreBundle\Service\Follow\FollowService;
use Morfeditorial\MachinimaCoreBundle\Service\Interaction\ContentViewService;
use Morfeditorial\MachinimaCoreBundle\Service\Recommendation\RecommendationPipeline;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AppController extends AbstractController
{
    public function __construct(
        private FollowService $followService,
        private ContentViewService $contentViewService,
        private AppPageService $appPageService,
    ) {
    }

    #[Route('/', name: 'app_index')]
    public function index(RecommendationPipeline $recommendationPipeline): Response
    {
        $feed = $recommendationPipeline->getRecommendations($this->getUser(), 20);

        return $this->render('@MachinimaCore/app/index.html.twig', [
            'feed' => $feed,
        ]);
    }

    #[Route('/categories', name: 'app_categories')]
    public function categories(): Response
    {
        return $this->render('@MachinimaCore/app/categories.html.twig', [
            'categories' => $this->appPageService->getCategories(),
        ]);
    }

    #[Route('/category/{id}', name: 'app_category', requirements: ['id' => '\d+'])]
    public function category(int $id): Response
    {
        $data = $this->appPageService->getCategory($id, $this->getUser());
        if (!$data) {
            throw $this->createNotFoundException();
        }

        return $this->render('@MachinimaCore/app/category.html.twig', $data);
    }

    #[Route('/authors', name: 'app_authors')]
    public function authors(): Response
    {
        return $this->render('@MachinimaCore/app/authors.html.twig', [
            'authors' => $this->appPageService->getAuthors($this->getUser()),
        ]);
    }

    #[Route('/author/{id}', name: 'app_author', requirements: ['id' => '\d+'])]
    public function author(int $id): Response
    {
        $data = $this->appPageService->getAuthorDetail($id, $this->getUser());
        if (!$data) {
            throw $this->createNotFoundException();
        }

        return $this->render('@MachinimaCore/app/author.html.twig', $data);
    }

    #[Route('/author/{id}/follow', name: 'app_author_follow', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function followAuthor(int $id): Response
    {
        $result = $this->followService->follow($this->getUser(), $id);
        if (!$result) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        return $this->json(['status' => 'followed']);
    }

    #[Route('/author/{id}/unfollow', name: 'app_author_unfollow', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function unfollowAuthor(int $id): Response
    {
        $result = $this->followService->unfollow($this->getUser(), $id);
        if (!$result) {
            return $this->json(['error' => 'Author not found'], 404);
        }

        return $this->json(['status' => 'unfollowed']);
    }

    #[Route('/post/{id}', name: 'app_post', requirements: ['id' => '\d+'])]
    public function post(int $id): Response
    {
        $data = $this->appPageService->getPostPageData($id, $this->getUser());
        if (!$data) {
            throw $this->createNotFoundException();
        }

        $this->contentViewService->trackView($this->getUser(), $data['post']);

        return $this->render('@MachinimaCore/app/post.html.twig', $data);
    }

    #[Route('/notifications', name: 'app_notifications')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function notifications(): Response
    {
        return $this->render('@MachinimaCore/app/notifications.html.twig', [
            'notifications' => $this->appPageService->getNotifications($this->getUser()),
        ]);
    }

    #[Route('/user/{id}', name: 'app_user', requirements: ['id' => '\d+'])]
    public function userProfile(int $id): Response
    {
        $data = $this->appPageService->getUserProfile($id, $this->getUser());
        if (!$data) {
            throw $this->createNotFoundException('User not found');
        }

        if (isset($data['self'])) {
            return $this->forward('App\Controller\Web\ProfileController::index');
        }

        return $this->render('@MachinimaCore/app/user.html.twig', $data);
    }
}
