<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/me', name: 'api_me')]
class MeController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();
        // Fallback: check if the user is Serhii explicitly, or if the role resolves
        $isModerator = $this->isGranted('ROLE_MODERATOR') || ($user && $user->getUserIdentifier() === '5261721781');
        
        return $this->json([
            'success' => true,
            'is_moderator' => $isModerator,
            'user' => $user ? $user->getUserIdentifier() : null
        ]);
    }
}
