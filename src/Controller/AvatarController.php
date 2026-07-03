<?php

namespace App\Controller;

use App\Service\Avatar\AvatarProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AvatarController extends AbstractController
{
    public function __construct(
        private AvatarProviderInterface $avatarProvider,
        private CacheInterface $cache,
    ) {
    }

    #[Route('/avatar/{userId}', name: 'app_avatar', requirements: ['userId' => '\d+'])]
    public function getAvatar(int $userId): Response
    {
        $avatarUrl = $this->cache->get('user_avatar_'.$userId, function (ItemInterface $item) use ($userId) {
            $item->expiresAfter(86400);

            return $this->avatarProvider->getAvatarUrl($userId);
        });

        if ('default' === $avatarUrl) {
            return $this->generateDefaultAvatar($userId);
        }

        // Cache the redirect itself in the browser for 1 hour
        $response = new RedirectResponse($avatarUrl);
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
    }

    private function generateDefaultAvatar(int $userId): Response
    {
        $identicon = new \Identicon\Identicon();
        $imageData = $identicon->getImageData((string) $userId, 100);

        $response = new Response($imageData);
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
