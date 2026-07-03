<?php

namespace App\Controller;

use App\Service\Media\MediaProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class MediaController extends AbstractController
{
    public function __construct(
        private MediaProviderInterface $mediaProvider,
        private CacheInterface $cache,
    ) {
    }

    #[Route('/media/{fileId}', name: 'app_media')]
    public function getMedia(string $fileId): Response
    {
        $mediaUrl = $this->cache->get('tg_media_'.$fileId, function (ItemInterface $item) use ($fileId) {
            $item->expiresAfter(86400);

            return $this->mediaProvider->getMediaUrl($fileId);
        });

        if ('default' === $mediaUrl) {
            return new RedirectResponse('https://placehold.co/600x400.png?text=Media+Not+Found');
        }

        $response = new RedirectResponse($mediaUrl);
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }
}
