<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MediaController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
    ) {
    }

    #[Route('/media/{fileId}', name: 'app_media')]
    public function getMedia(string $fileId): Response
    {
        $token = $_ENV['TELEGRAM_BOT_TOKEN'] ?? '';

        $mediaUrl = $this->cache->get('tg_media_'.$fileId, function (ItemInterface $item) use ($fileId, $token) {
            // Cache the media URL for 24 hours
            $item->expiresAfter(86400);

            if (empty($token)) {
                return 'default';
            }

            try {
                // Get file path from Telegram
                $fileResponse = $this->httpClient->request('GET', "https://api.telegram.org/bot{$token}/getFile", [
                    'query' => ['file_id' => $fileId],
                ]);

                $fileData = $fileResponse->toArray(false);

                if (!empty($fileData['result']['file_path'])) {
                    return "https://api.telegram.org/file/bot{$token}/".$fileData['result']['file_path'];
                }
            } catch (\Exception $e) {
                // Ignore exceptions and fallback
            }

            return 'default';
        });

        if ('default' === $mediaUrl) {
            // Return a default placeholder if Telegram image can't be fetched
            return new RedirectResponse('https://placehold.co/600x400.png?text=Media+Not+Found');
        }

        // Redirect to the actual Telegram file URL
        $response = new RedirectResponse($mediaUrl);
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
