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
        $colors = [
            '#000000', '#2B2B2B', '#E53935', '#D81B60', '#8E24AA', '#5E35B1',
            '#3949AB', '#1E88E5', '#039BE5', '#00ACC1', '#00897B', '#43A047',
            '#7CB342', '#F4511E', '#6D4C41',
        ];
        // Brutalist colors mix

        $color = $colors[$userId % count($colors)];
        $initial = mb_substr((string) $userId, -1);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="'.$color.'"/>
            <text x="50%" y="54%" fill="white" font-size="45" font-family="sans-serif" font-weight="bold" dominant-baseline="central" text-anchor="middle">'.$initial.'</text>
        </svg>';

        $response = new Response($svg);
        $response->headers->set('Content-Type', 'image/svg+xml');
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
