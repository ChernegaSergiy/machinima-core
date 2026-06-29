<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AvatarController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
    }

    #[Route('/avatar/{userId}', name: 'app_avatar', requirements: ['userId' => '\d+'])]
    public function getAvatar(int $userId): Response
    {
        $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

        $avatarUrl = $this->cache->get('user_avatar_'.$userId, function (ItemInterface $item) use ($userId, $token) {
            // Cache the avatar URL for 24 hours to avoid hitting Telegram rate limits
            $item->expiresAfter(86400);

            if (empty($token)) {
                return 'default';
            }

            try {
                // Fetch user profile photos
                $response = $this->httpClient->request('GET', "https://api.telegram.org/bot{$token}/getUserProfilePhotos", [
                    'query' => ['user_id' => $userId, 'limit' => 1],
                ]);
                $data = $response->toArray(false);

                if (!empty($data['result']['photos'][0][0]['file_id'])) {
                    $fileId = $data['result']['photos'][0][0]['file_id'];

                    // Get file path
                    $fileResponse = $this->httpClient->request('GET', "https://api.telegram.org/bot{$token}/getFile", [
                        'query' => ['file_id' => $fileId],
                    ]);
                    $fileData = $fileResponse->toArray(false);

                    if (!empty($fileData['result']['file_path'])) {
                        return "https://api.telegram.org/file/bot{$token}/".$fileData['result']['file_path'];
                    }
                }
            } catch (\Exception $e) {
                // Ignore exceptions (e.g. user blocked bot or network error) and fallback
            }

            return 'default';
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
