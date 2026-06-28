<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/me', name: 'api_me')]
class MeController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function me(): JsonResponse
    {
        return $this->json([
            'success' => true,
            'is_moderator' => $this->isGranted('ROLE_MODERATOR'),
        ]);
    }
}
